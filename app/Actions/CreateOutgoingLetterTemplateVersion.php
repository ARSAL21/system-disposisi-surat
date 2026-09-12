<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\User;
use App\Services\OutgoingLetterTemplateStorage;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateOutgoingLetterTemplateVersion
{
    public function __construct(
        private readonly OutgoingLetterTemplateStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    /** @param array{qr_page_mode:string,qr_page_number:int|null,qr_x_ratio:float,qr_y_ratio:float,qr_width_ratio:float,qr_height_ratio:float} $qr */
    public function execute(User $actor, OutgoingLetterTemplate $template, UploadedFile $file, array $qr): OutgoingLetterTemplateVersion
    {
        $stored = $this->storage->store($file, $template->organizational_unit_id);

        try {
            return DB::transaction(function () use ($actor, $template, $stored, $qr): OutgoingLetterTemplateVersion {
                $lockedTemplate = OutgoingLetterTemplate::query()->whereKey($template->getKey())->lockForUpdate()->firstOrFail();
                if (! $lockedTemplate->is_active) {
                    throw StandaloneOutgoingStateConflict::invalidTemplate();
                }

                $assignment = $this->assignmentResolver->lockSectionHeadAssignmentForUnit($actor, $lockedTemplate->organizational_unit_id);
                $versions = OutgoingLetterTemplateVersion::query()
                    ->where('outgoing_letter_template_id', $lockedTemplate->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->get();
                $previous = $versions->first();
                if (! $previous instanceof OutgoingLetterTemplateVersion) {
                    throw StandaloneOutgoingStateConflict::invalidTemplate();
                }

                if ($versions->contains(fn (OutgoingLetterTemplateVersion $version): bool => hash_equals($version->sha256, $stored->sha256))) {
                    throw ValidationException::withMessages(['document' => 'Versi DOCX yang sama sudah tercatat pada template ini.']);
                }

                $version = new OutgoingLetterTemplateVersion;
                $version->outgoing_letter_template_id = $lockedTemplate->getKey();
                $version->version_number = $previous->version_number + 1;
                $version->replaces_version_id = $previous->getKey();
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $version->qr_page_mode = $qr['qr_page_mode'];
                $version->qr_page_number = $qr['qr_page_number'];
                $version->qr_x_ratio = $qr['qr_x_ratio'];
                $version->qr_y_ratio = $qr['qr_y_ratio'];
                $version->qr_width_ratio = $qr['qr_width_ratio'];
                $version->qr_height_ratio = $qr['qr_height_ratio'];
                $version->uploaded_by_user_id = $actor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();

                $this->audit->execute(
                    $actor,
                    AuditAction::OutgoingTemplateVersionCreated,
                    'outgoing_letter_template',
                    $lockedTemplate->getKey(),
                    newValues: ['version_number' => $version->version_number],
                    actorPositionAssignment: $assignment,
                );

                return $version;
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->storage->delete($stored);
            throw $exception;
        }
    }
}

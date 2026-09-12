<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\User;
use App\Services\OutgoingLetterTemplateStorage;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateOutgoingLetterTemplate
{
    public function __construct(
        private readonly OutgoingLetterTemplateStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    /** @param array{qr_page_mode:string,qr_page_number:int|null,qr_x_ratio:float,qr_y_ratio:float,qr_width_ratio:float,qr_height_ratio:float} $qr */
    public function execute(User $actor, int $unitId, string $code, string $name, UploadedFile $file, array $qr): OutgoingLetterTemplate
    {
        $stored = $this->storage->store($file, $unitId);

        try {
            return DB::transaction(function () use ($actor, $unitId, $code, $name, $stored, $qr): OutgoingLetterTemplate {
                $unit = OrganizationalUnit::query()->whereKey($unitId)->where('is_active', true)->lockForUpdate()->firstOrFail();
                $assignment = $this->assignmentResolver->lockSectionHeadAssignmentForUnit($actor, (int) $unit->getKey());
                if (OutgoingLetterTemplate::query()
                    ->where('organizational_unit_id', $unit->getKey())
                    ->where('code', $code)
                    ->lockForUpdate()
                    ->exists()) {
                    throw ValidationException::withMessages(['code' => 'Kode template sudah digunakan pada Bagian ini.']);
                }

                $template = new OutgoingLetterTemplate;
                $template->organizational_unit_id = $unit->getKey();
                $template->code = $code;
                $template->name = $name;
                $template->is_active = true;
                $template->save();

                $version = new OutgoingLetterTemplateVersion;
                $version->outgoing_letter_template_id = $template->getKey();
                $version->version_number = 1;
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $this->fillQrPlacement($version, $qr);
                $version->uploaded_by_user_id = $actor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();

                $this->audit->execute(
                    $actor,
                    AuditAction::OutgoingTemplateVersionCreated,
                    'outgoing_letter_template',
                    $template->getKey(),
                    newValues: ['version_number' => 1, 'code' => $template->code, 'is_active' => true],
                    actorPositionAssignment: $assignment,
                );

                return $template;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            $this->storage->delete($stored);
            throw ValidationException::withMessages(['code' => 'Kode template sudah digunakan pada Bagian ini.']);
        } catch (Throwable $exception) {
            $this->storage->delete($stored);
            throw $exception;
        }
    }

    /** @param array{qr_page_mode:string,qr_page_number:int|null,qr_x_ratio:float,qr_y_ratio:float,qr_width_ratio:float,qr_height_ratio:float} $qr */
    private function fillQrPlacement(OutgoingLetterTemplateVersion $version, array $qr): void
    {
        $version->qr_page_mode = $qr['qr_page_mode'];
        $version->qr_page_number = $qr['qr_page_number'];
        $version->qr_x_ratio = $qr['qr_x_ratio'];
        $version->qr_y_ratio = $qr['qr_y_ratio'];
        $version->qr_width_ratio = $qr['qr_width_ratio'];
        $version->qr_height_ratio = $qr['qr_height_ratio'];
    }
}

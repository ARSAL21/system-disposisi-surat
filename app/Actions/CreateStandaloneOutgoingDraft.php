<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\Position;
use App\Models\StandaloneOutgoingCopyRecipient;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\StandaloneOutgoingDocumentStorage;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class CreateStandaloneOutgoingDraft
{
    public function __construct(
        private readonly StandaloneOutgoingDocumentStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    /**
     * @param  array{recipient_name:string,recipient_organization:?string,recipient_position:?string,recipient_address:?string,recipient_email:?string,subject:string,summary:?string}  $attributes
     * @param  list<int>  $copyPositionIds
     */
    public function execute(
        User $actor,
        int $unitId,
        int $templateVersionId,
        array $attributes,
        array $copyPositionIds,
        UploadedFile $file,
    ): StandaloneOutgoingDraft {
        $publicId = (string) Str::ulid();
        $stored = $this->storage->store($file, $publicId);

        try {
            return DB::transaction(function () use ($actor, $unitId, $templateVersionId, $attributes, $copyPositionIds, $stored, $publicId): StandaloneOutgoingDraft {
                $templateVersion = $this->lockValidTemplateVersion($templateVersionId, $unitId);
                $assignment = $this->assignmentResolver->lockStaffAssignmentForUnit($actor, $unitId);
                $copyPositionIds = $this->lockValidCopyPositions($copyPositionIds);

                $draft = new StandaloneOutgoingDraft;
                $draft->public_id = $publicId;
                $draft->organizational_unit_id = $unitId;
                $draft->outgoing_letter_template_version_id = $templateVersion->getKey();
                $this->fillDraft($draft, $attributes);
                $draft->status = StandaloneOutgoingDraftStatus::Draft;
                $draft->created_by_user_id = $actor->getKey();
                $draft->created_by_position_assignment_id = $assignment->getKey();
                $draft->save();

                foreach ($copyPositionIds as $positionId) {
                    $copyRecipient = new StandaloneOutgoingCopyRecipient;
                    $copyRecipient->standalone_outgoing_draft_id = $draft->getKey();
                    $copyRecipient->position_id = $positionId;
                    $copyRecipient->save();
                }

                $version = new StandaloneOutgoingDocumentVersion;
                $version->standalone_outgoing_draft_id = $draft->getKey();
                $version->version_number = 1;
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $version->uploaded_by_user_id = $actor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();

                $this->audit->execute(
                    $actor,
                    AuditAction::StandaloneOutgoingDraftCreated,
                    'standalone_outgoing_draft',
                    $draft->getKey(),
                    newValues: ['status' => $draft->status->value, 'document_version' => 1],
                    actorPositionAssignment: $assignment,
                );

                return $draft;
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->storage->delete($stored);
            throw $exception;
        }
    }

    private function lockValidTemplateVersion(int $templateVersionId, int $unitId): OutgoingLetterTemplateVersion
    {
        $templateId = OutgoingLetterTemplateVersion::query()
            ->whereKey($templateVersionId)
            ->value('outgoing_letter_template_id');
        if ($templateId === null) {
            throw StandaloneOutgoingStateConflict::invalidTemplate();
        }

        $template = OutgoingLetterTemplate::query()->whereKey($templateId)->lockForUpdate()->firstOrFail();
        $version = OutgoingLetterTemplateVersion::query()
            ->with('template')
            ->whereKey($templateVersionId)
            ->where('outgoing_letter_template_id', $template->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if (! $template->is_active || (int) $template->organizational_unit_id !== $unitId) {
            throw StandaloneOutgoingStateConflict::invalidTemplate();
        }

        return $version;
    }

    /**
     * @param  list<int>  $copyPositionIds
     * @return list<int>
     */
    private function lockValidCopyPositions(array $copyPositionIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $copyPositionIds)));
        if ($ids === []) {
            return [];
        }

        $positions = Position::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereHas('positionLevel', fn ($level) => $level->where('is_active', true)->whereIn('code', [
                OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
                OrganizationCatalog::ASSISTANT_LEVEL,
                OrganizationCatalog::SECTION_HEAD_LEVEL,
            ]))
            ->lockForUpdate()
            ->get();

        if ($positions->count() !== count($ids)) {
            throw StandaloneOutgoingStateConflict::invalidCopyRecipient();
        }

        return array_values($positions->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all());
    }

    /** @param array{recipient_name:string,recipient_organization:?string,recipient_position:?string,recipient_address:?string,recipient_email:?string,subject:string,summary:?string} $attributes */
    private function fillDraft(StandaloneOutgoingDraft $draft, array $attributes): void
    {
        $draft->recipient_name = $attributes['recipient_name'];
        $draft->recipient_organization = $attributes['recipient_organization'];
        $draft->recipient_position = $attributes['recipient_position'];
        $draft->recipient_address = $attributes['recipient_address'];
        $draft->recipient_email = $attributes['recipient_email'];
        $draft->subject = $attributes['subject'];
        $draft->summary = $attributes['summary'];
    }
}

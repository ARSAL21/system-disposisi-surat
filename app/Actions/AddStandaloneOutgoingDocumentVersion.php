<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Services\StandaloneOutgoingDocumentStorage;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AddStandaloneOutgoingDocumentVersion
{
    public function __construct(
        private readonly StandaloneOutgoingDocumentStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, StandaloneOutgoingDraft $draft, UploadedFile $file, ?string $note): StandaloneOutgoingDocumentVersion
    {
        $stored = $this->storage->store($file, $draft->public_id);

        try {
            return DB::transaction(function () use ($actor, $draft, $stored, $note): StandaloneOutgoingDocumentVersion {
                $lockedDraft = StandaloneOutgoingDraft::query()->whereKey($draft->getKey())->lockForUpdate()->firstOrFail();
                if (! in_array($lockedDraft->status, [StandaloneOutgoingDraftStatus::Draft, StandaloneOutgoingDraftStatus::RevisionRequired], true)
                    || (int) $lockedDraft->created_by_user_id !== (int) $actor->getKey()) {
                    throw StandaloneOutgoingStateConflict::stale();
                }

                $versions = StandaloneOutgoingDocumentVersion::query()
                    ->where('standalone_outgoing_draft_id', $lockedDraft->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->get();
                $previous = $versions->first();
                if (! $previous instanceof StandaloneOutgoingDocumentVersion) {
                    throw StandaloneOutgoingStateConflict::stale();
                }

                if ($versions->contains(fn (StandaloneOutgoingDocumentVersion $version): bool => hash_equals($version->sha256, $stored->sha256))) {
                    throw ValidationException::withMessages(['document' => 'Versi PDF yang sama sudah tercatat pada konsep surat ini.']);
                }

                $assignment = $this->assignmentResolver->lockDeliveryActorAssignmentForUnit($actor, $lockedDraft->organizational_unit_id);

                $version = new StandaloneOutgoingDocumentVersion;
                $version->standalone_outgoing_draft_id = $lockedDraft->getKey();
                $version->version_number = $previous->version_number + 1;
                $version->replaces_version_id = $previous->getKey();
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $version->revision_note = $note;
                $version->uploaded_by_user_id = $actor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();

                $this->audit->execute(
                    $actor,
                    AuditAction::StandaloneOutgoingDocumentVersionCreated,
                    'standalone_outgoing_draft',
                    $lockedDraft->getKey(),
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

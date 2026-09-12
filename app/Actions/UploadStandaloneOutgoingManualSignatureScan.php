<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\User;
use App\Services\StandaloneOutgoingFinalDocumentStorage;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UploadStandaloneOutgoingManualSignatureScan
{
    public function __construct(
        private readonly StandaloneOutgoingFinalDocumentStorage $storage,
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, UploadedFile $file): OutgoingLetterDocumentVersion
    {
        $stored = $this->storage->store($file, $target);

        try {
            return DB::transaction(function () use ($actor, $target, $stored): OutgoingLetterDocumentVersion {
                ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
                if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                    || $outgoing->status !== OutgoingLetterStatus::AwaitingManualSignature
                    || $draft->status !== StandaloneOutgoingDraftStatus::AwaitingManualSignature) {
                    throw StandaloneOutgoingStateConflict::stale();
                }

                $latestVersionNumber = OutgoingLetterDocumentVersion::query()
                    ->where('outgoing_letter_id', $outgoing->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->value('version_number');
                $latestVersionId = OutgoingLetterDocumentVersion::query()
                    ->where('outgoing_letter_id', $outgoing->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->value('id');
                if (OutgoingLetterDocumentVersion::query()->where('outgoing_letter_id', $outgoing->getKey())->where('sha256', $stored->sha256)->exists()) {
                    throw ValidationException::withMessages(['manual_scan' => 'PDF scan yang sama sudah tercatat.']);
                }

                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                $assignment = $this->assignmentResolver->hasStaffAssignmentForUnit($lockedActor, $draft->organizational_unit_id)
                    ? $this->assignmentResolver->lockStaffAssignmentForUnit($lockedActor, $draft->organizational_unit_id)
                    : $this->assignmentResolver->lockSectionHeadAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
                $version = new OutgoingLetterDocumentVersion;
                $version->outgoing_letter_id = $outgoing->getKey();
                $version->version_number = is_numeric($latestVersionNumber)
                    ? (int) $latestVersionNumber + 1
                    : 1;
                $version->replaces_version_id = is_numeric($latestVersionId)
                    ? (int) $latestVersionId
                    : null;
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $version->upload_note = 'Scan tanda tangan fisik Sekda.';
                $version->uploaded_by_user_id = $lockedActor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();
                $outgoing->status = OutgoingLetterStatus::ManualScanReview;
                $outgoing->save();
                $draft->status = StandaloneOutgoingDraftStatus::ManualScanReview;
                $draft->save();

                $this->audit->execute(
                    actor: $lockedActor,
                    action: AuditAction::StandaloneOutgoingManualScanUploaded,
                    subjectType: 'standalone_outgoing_draft',
                    subjectId: $draft->getKey(),
                    newValues: [
                        'outgoing_letter_id' => $outgoing->getKey(),
                        'document_version' => $version->version_number,
                    ],
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

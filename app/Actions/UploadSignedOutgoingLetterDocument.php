<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingLetterReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\User;
use App\Services\OutgoingLetterDocumentStorage;
use App\Services\OutgoingLetterLockService;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UploadSignedOutgoingLetterDocument
{
    public function __construct(
        private readonly OutgoingLetterDocumentStorage $storage,
        private readonly OutgoingLetterLockService $lockService,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(
        User $actor,
        OutgoingLetter $target,
        UploadedFile $file,
        string $uploadNote,
    ): OutgoingLetterDocumentVersion {
        $stored = $this->storage->store($file, $target);

        try {
            return DB::transaction(function () use ($actor, $target, $stored, $uploadNote): OutgoingLetterDocumentVersion {
                $context = $this->lockService->lock($target);
                $outgoing = $context['outgoing'];

                if ($context['dossier']->status !== LetterResponseDossierStatus::Finalized
                    || ! in_array($outgoing->status, [
                        OutgoingLetterStatus::NumberAssigned,
                        OutgoingLetterStatus::SignedDocumentUploaded,
                    ], true)) {
                    throw OutgoingLetterStateConflict::stale();
                }

                $latest = OutgoingLetterDocumentVersion::query()
                    ->where('outgoing_letter_id', $outgoing->getKey())
                    ->with('review')
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->first();

                if ($outgoing->status === OutgoingLetterStatus::NumberAssigned && $latest !== null) {
                    throw OutgoingLetterStateConflict::stale();
                }

                if ($outgoing->status === OutgoingLetterStatus::SignedDocumentUploaded
                    && ($latest === null || $latest->review?->decision !== OutgoingLetterReviewDecision::Returned)) {
                    throw OutgoingLetterStateConflict::stale();
                }

                if (OutgoingLetterDocumentVersion::query()
                    ->where('outgoing_letter_id', $outgoing->getKey())
                    ->where('sha256', $stored->sha256)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'signed_document' => 'PDF identik dengan versi final yang sudah tersimpan.',
                    ]);
                }

                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                $ownerPositionId = (int) $outgoing->sourceDocumentVersion()
                    ->join('letter_response_documents', 'letter_response_documents.id', '=', 'letter_response_document_versions.letter_response_document_id')
                    ->value('letter_response_documents.owner_position_id');
                $ownsSource = $lockedActor->positionAssignments()
                    ->where('position_id', $ownerPositionId)
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->exists();
                $assignment = $ownsSource
                    ? $this->assignmentResolver->lockPosition($lockedActor, $ownerPositionId)
                    : $this->assignmentResolver->lockGeneralAffairsOfficer($lockedActor);

                $version = new OutgoingLetterDocumentVersion;
                $version->outgoing_letter_id = $outgoing->getKey();
                $version->version_number = $latest instanceof OutgoingLetterDocumentVersion
                    ? $latest->version_number + 1
                    : 1;
                $version->replaces_version_id = $latest?->getKey();
                $version->storage_disk = $stored->disk;
                $version->storage_path = $stored->path;
                $version->original_filename = $stored->originalFilename;
                $version->mime_type = $stored->mimeType;
                $version->size_bytes = $stored->sizeBytes;
                $version->sha256 = $stored->sha256;
                $version->upload_note = trim($uploadNote);
                $version->uploaded_by_user_id = $lockedActor->getKey();
                $version->uploaded_by_position_assignment_id = $assignment->getKey();
                $version->save();

                if ($outgoing->status === OutgoingLetterStatus::NumberAssigned) {
                    $outgoing->status = OutgoingLetterStatus::SignedDocumentUploaded;
                    $outgoing->save();
                }

                $this->recordAudit->execute(
                    actor: $lockedActor,
                    action: AuditAction::OutgoingLetterDocumentVersionCreated,
                    subjectType: 'incoming_letter',
                    subjectId: $context['letter']->getKey(),
                    newValues: [
                        'outgoing_letter_id' => $outgoing->getKey(),
                        'version_number' => $version->version_number,
                        'sha256' => $version->sha256,
                        'size_bytes' => $version->size_bytes,
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

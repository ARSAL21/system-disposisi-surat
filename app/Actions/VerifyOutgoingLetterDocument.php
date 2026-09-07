<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingLetterReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentReview;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\User;
use App\Services\OutgoingLetterDocumentStorage;
use App\Services\OutgoingLetterLockService;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class VerifyOutgoingLetterDocument
{
    public function __construct(
        private readonly OutgoingLetterLockService $lockService,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly OutgoingLetterDocumentStorage $storage,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, ?string $note): OutgoingLetterDocumentReview
    {
        return DB::transaction(function () use ($actor, $target, $note): OutgoingLetterDocumentReview {
            $context = $this->lockService->lock($target);
            $outgoing = $context['outgoing'];
            $version = OutgoingLetterDocumentVersion::query()
                ->where('outgoing_letter_id', $outgoing->getKey())
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->first();

            if ($context['dossier']->status !== LetterResponseDossierStatus::Finalized
                || $outgoing->status !== OutgoingLetterStatus::SignedDocumentUploaded
                || ! $version instanceof OutgoingLetterDocumentVersion
                || $version->review()->exists()) {
                throw OutgoingLetterStateConflict::stale();
            }

            $this->storage->validate($outgoing, $version);
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockGeneralAffairsHead($lockedActor);
            $review = new OutgoingLetterDocumentReview;
            $review->outgoing_letter_document_version_id = $version->getKey();
            $review->decision = OutgoingLetterReviewDecision::Verified;
            $review->note = $note !== null && trim($note) !== '' ? trim($note) : null;
            $review->decided_by_user_id = $lockedActor->getKey();
            $review->decided_by_position_assignment_id = $assignment->getKey();
            $review->save();

            $outgoing->status = OutgoingLetterStatus::AdminVerified;
            $outgoing->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::OutgoingLetterAdminVerified,
                subjectType: 'incoming_letter',
                subjectId: $context['letter']->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::SignedDocumentUploaded->value],
                newValues: [
                    'outgoing_letter_id' => $outgoing->getKey(),
                    'status' => OutgoingLetterStatus::AdminVerified->value,
                    'document_version' => $version->version_number,
                ],
                actorPositionAssignment: $assignment,
            );

            return $review;
        }, attempts: 3);
    }
}

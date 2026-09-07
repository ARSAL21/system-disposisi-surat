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
use App\Services\OutgoingLetterLockService;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class ReturnOutgoingLetterDocument
{
    public function __construct(
        private readonly OutgoingLetterLockService $lockService,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, string $reason): OutgoingLetterDocumentReview
    {
        return DB::transaction(function () use ($actor, $target, $reason): OutgoingLetterDocumentReview {
            $context = $this->lockService->lock($target);
            $outgoing = $context['outgoing'];
            $version = $this->lockCurrentVersion($outgoing);

            if ($context['dossier']->status !== LetterResponseDossierStatus::Finalized
                || $outgoing->status !== OutgoingLetterStatus::SignedDocumentUploaded
                || $version->review()->exists()) {
                throw OutgoingLetterStateConflict::stale();
            }

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockGeneralAffairsHead($lockedActor);
            $review = new OutgoingLetterDocumentReview;
            $review->outgoing_letter_document_version_id = $version->getKey();
            $review->decision = OutgoingLetterReviewDecision::Returned;
            $review->note = trim($reason);
            $review->decided_by_user_id = $lockedActor->getKey();
            $review->decided_by_position_assignment_id = $assignment->getKey();
            $review->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::OutgoingLetterDocumentReturned,
                subjectType: 'incoming_letter',
                subjectId: $context['letter']->getKey(),
                newValues: [
                    'outgoing_letter_id' => $outgoing->getKey(),
                    'document_version' => $version->version_number,
                    'decision' => OutgoingLetterReviewDecision::Returned->value,
                ],
                actorPositionAssignment: $assignment,
            );

            return $review;
        }, attempts: 3);
    }

    private function lockCurrentVersion(OutgoingLetter $letter): OutgoingLetterDocumentVersion
    {
        $version = OutgoingLetterDocumentVersion::query()
            ->where('outgoing_letter_id', $letter->getKey())
            ->orderByDesc('version_number')
            ->lockForUpdate()
            ->first();

        if (! $version instanceof OutgoingLetterDocumentVersion) {
            throw OutgoingLetterStateConflict::missingDocument();
        }

        return $version;
    }
}

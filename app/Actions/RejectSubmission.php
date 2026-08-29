<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\SubmissionDecisionOutcome;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionStateConflict;
use App\Models\LetterSubmission;
use App\Models\User;
use App\Services\CreateSubmissionDecision;
use App\Services\IntakeApprovalPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

class RejectSubmission
{
    public function __construct(
        private readonly IntakeApprovalPositionAssignmentResolver $positionAssignmentResolver,
        private readonly CreateSubmissionDecision $createDecision,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, LetterSubmission $submission, string $note): LetterSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $note): LetterSubmission {
            $lockedSubmission = LetterSubmission::query()
                ->whereKey($submission->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubmission->status !== SubmissionStatus::ReadyForApproval) {
                throw SubmissionStateConflict::expectedReadyForApproval($lockedSubmission->status);
            }

            $positionAssignment = $this->positionAssignmentResolver->lockActiveAssignment($actor);
            $decision = $this->createDecision->execute(
                actor: $actor,
                positionAssignment: $positionAssignment,
                submission: $lockedSubmission,
                outcome: SubmissionDecisionOutcome::Rejected,
                note: $note,
            );

            $this->recordAudit->execute(
                actor: $actor,
                action: AuditAction::SubmissionRejected,
                subjectType: 'letter_submission',
                subjectId: $lockedSubmission->getKey(),
                oldValues: ['status' => SubmissionStatus::ReadyForApproval->value],
                newValues: ['status' => SubmissionStatus::Rejected->value],
                metadata: [
                    'public_id' => $lockedSubmission->public_id,
                    'submission_decision_id' => $decision->getKey(),
                ],
                actorPositionAssignment: $positionAssignment,
            );

            return $lockedSubmission;
        }, attempts: 3);
    }
}

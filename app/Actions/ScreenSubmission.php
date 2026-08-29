<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\SubmissionReviewOutcome;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionStateConflict;
use App\Intake\SubmissionScreeningChecklist;
use App\Models\LetterSubmission;
use App\Models\SubmissionReview;
use App\Models\User;
use App\Services\IntakePositionAssignmentResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScreenSubmission
{
    public function __construct(
        private readonly IntakePositionAssignmentResolver $positionAssignmentResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    /**
     * @param  list<array{id: string, checked: bool}>  $checklist
     */
    public function execute(
        User $actor,
        LetterSubmission $submission,
        SubmissionReviewOutcome $outcome,
        array $checklist,
        ?string $note,
    ): LetterSubmission {
        return DB::transaction(function () use ($actor, $submission, $outcome, $checklist, $note): LetterSubmission {
            $lockedSubmission = LetterSubmission::query()
                ->lockForUpdate()
                ->whereKey($submission->getKey())
                ->firstOrFail();

            if ($lockedSubmission->status !== SubmissionStatus::Submitted) {
                throw SubmissionStateConflict::expectedSubmitted($lockedSubmission->status);
            }

            $positionAssignment = $this->positionAssignmentResolver
                ->lockActiveAssignment($actor);
            $normalizedChecklist = SubmissionScreeningChecklist::normalize($checklist);
            $normalizedNote = trim((string) $note);

            if ($outcome === SubmissionReviewOutcome::RevisionRequired
                && $normalizedNote === '') {
                throw ValidationException::withMessages([
                    'note' => 'A correction note is required.',
                ]);
            }

            if ($outcome === SubmissionReviewOutcome::ReadyForApproval
                && ! SubmissionScreeningChecklist::isComplete($normalizedChecklist)) {
                throw ValidationException::withMessages([
                    'checklist' => 'Every checklist item must be complete before approval review.',
                ]);
            }

            if ($outcome === SubmissionReviewOutcome::ReadyForApproval
                && ! $lockedSubmission->document()->exists()) {
                throw ValidationException::withMessages([
                    'document' => 'A submission document is required before approval review.',
                ]);
            }

            $review = new SubmissionReview;
            $review->letter_submission_id = $lockedSubmission->getKey();
            $review->outcome = $outcome;
            $review->checklist = $normalizedChecklist;
            $review->note = $normalizedNote !== '' ? $normalizedNote : null;
            $review->created_by_user_id = $actor->getKey();
            $review->created_by_position_assignment_id = $positionAssignment->getKey();
            $review->save();

            $lockedSubmission->status = $outcome->submissionStatus();
            $lockedSubmission->save();

            $this->recordAudit->execute(
                actor: $actor,
                action: match ($outcome) {
                    SubmissionReviewOutcome::RevisionRequired => AuditAction::SubmissionRevisionRequested,
                    SubmissionReviewOutcome::ReadyForApproval => AuditAction::SubmissionReadyForApproval,
                },
                subjectType: 'letter_submission',
                subjectId: $lockedSubmission->getKey(),
                oldValues: ['status' => SubmissionStatus::Submitted->value],
                newValues: ['status' => $outcome->submissionStatus()->value],
                metadata: [
                    'public_id' => $lockedSubmission->public_id,
                    'submission_review_id' => $review->getKey(),
                ],
                actorPositionAssignment: $positionAssignment,
            );

            return $lockedSubmission;
        }, attempts: 3);
    }
}

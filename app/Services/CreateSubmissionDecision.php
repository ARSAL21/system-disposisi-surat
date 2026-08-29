<?php

namespace App\Services;

use App\Enums\SubmissionDecisionOutcome;
use App\Models\LetterSubmission;
use App\Models\PositionAssignment;
use App\Models\SubmissionDecision;
use App\Models\User;

class CreateSubmissionDecision
{
    public function execute(
        User $actor,
        PositionAssignment $positionAssignment,
        LetterSubmission $submission,
        SubmissionDecisionOutcome $outcome,
        ?string $note,
    ): SubmissionDecision {
        $decision = new SubmissionDecision;
        $decision->letter_submission_id = $submission->getKey();
        $decision->outcome = $outcome;
        $decision->note = ($note = trim((string) $note)) !== '' ? $note : null;
        $decision->created_by_user_id = $actor->getKey();
        $decision->created_by_position_assignment_id = $positionAssignment->getKey();
        $decision->save();

        $submission->status = $outcome->submissionStatus();
        $submission->save();

        return $decision;
    }
}

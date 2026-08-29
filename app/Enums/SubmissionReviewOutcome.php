<?php

namespace App\Enums;

enum SubmissionReviewOutcome: string
{
    case RevisionRequired = 'REVISION_REQUIRED';
    case ReadyForApproval = 'READY_FOR_APPROVAL';

    public function submissionStatus(): SubmissionStatus
    {
        return SubmissionStatus::from($this->value);
    }
}

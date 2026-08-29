<?php

namespace App\Enums;

enum SubmissionDecisionOutcome: string
{
    case InternalRevisionRequired = 'INTERNAL_REVISION_REQUIRED';
    case Rejected = 'REJECTED';
    case Registered = 'REGISTERED';

    public function submissionStatus(): SubmissionStatus
    {
        return match ($this) {
            self::InternalRevisionRequired => SubmissionStatus::InternalRevisionRequired,
            self::Rejected => SubmissionStatus::Rejected,
            self::Registered => SubmissionStatus::Registered,
        };
    }
}

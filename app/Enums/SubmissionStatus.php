<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case RevisionRequired = 'REVISION_REQUIRED';
    case ReadyForApproval = 'READY_FOR_APPROVAL';
    case InternalRevisionRequired = 'INTERNAL_REVISION_REQUIRED';
    case Registered = 'REGISTERED';
    case Rejected = 'REJECTED';
}

<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case Registered = 'REGISTERED';
    case Rejected = 'REJECTED';
}

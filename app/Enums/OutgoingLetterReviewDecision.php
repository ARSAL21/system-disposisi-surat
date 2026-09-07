<?php

namespace App\Enums;

enum OutgoingLetterReviewDecision: string
{
    case Verified = 'VERIFIED';
    case Returned = 'RETURNED';
}

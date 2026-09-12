<?php

namespace App\Enums;

enum ManualSignatureReviewDecision: string
{
    case Verified = 'VERIFIED';
    case Returned = 'RETURNED';
}

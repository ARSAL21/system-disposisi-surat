<?php

namespace App\Enums;

enum StandaloneOutgoingReviewDecision: string
{
    case Approved = 'APPROVED';
    case Returned = 'RETURNED';
}

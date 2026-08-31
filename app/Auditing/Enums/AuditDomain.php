<?php

namespace App\Auditing\Enums;

enum AuditDomain: string
{
    case Account = 'account';
    case Authorization = 'authorization';
    case Organization = 'organization';
    case Submission = 'submission';
    case IntakeReview = 'intake_review';
    case IntakeDecision = 'intake_decision';
    case Registration = 'registration';
    case Document = 'document';
}

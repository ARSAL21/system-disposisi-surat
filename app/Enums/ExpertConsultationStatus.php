<?php

namespace App\Enums;

enum ExpertConsultationStatus: string
{
    case Pending = 'PENDING';
    case Reported = 'REPORTED';
    case Cancelled = 'CANCELLED';
}

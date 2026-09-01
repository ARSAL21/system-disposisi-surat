<?php

namespace App\Enums;

enum DispositionRecipientStatus: string
{
    case Pending = 'PENDING';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
}

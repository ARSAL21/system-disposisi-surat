<?php

namespace App\Enums;

enum IncomingLetterStatus: string
{
    case Registered = 'REGISTERED';
    case Routed = 'ROUTED';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
}

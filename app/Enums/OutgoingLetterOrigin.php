<?php

namespace App\Enums;

enum OutgoingLetterOrigin: string
{
    case Response = 'RESPONSE';
    case Standalone = 'STANDALONE';
}

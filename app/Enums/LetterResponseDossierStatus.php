<?php

namespace App\Enums;

enum LetterResponseDossierStatus: string
{
    case Open = 'OPEN';
    case Finalized = 'FINALIZED';
}

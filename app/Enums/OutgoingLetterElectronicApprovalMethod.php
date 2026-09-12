<?php

namespace App\Enums;

enum OutgoingLetterElectronicApprovalMethod: string
{
    case Qr = 'QR';
    case ManualSignature = 'MANUAL_SIGNATURE';
}

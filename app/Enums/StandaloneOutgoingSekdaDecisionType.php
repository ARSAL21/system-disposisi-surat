<?php

namespace App\Enums;

enum StandaloneOutgoingSekdaDecisionType: string
{
    case QrApproved = 'QR_APPROVED';
    case ManualSignatureSelected = 'MANUAL_SIGNATURE_SELECTED';
    case Returned = 'RETURNED';
}

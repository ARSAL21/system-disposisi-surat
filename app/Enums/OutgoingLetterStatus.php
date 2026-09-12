<?php

namespace App\Enums;

enum OutgoingLetterStatus: string
{
    case Authorized = 'AUTHORIZED';
    case NumberAssigned = 'NUMBER_ASSIGNED';
    case SignedDocumentUploaded = 'SIGNED_DOCUMENT_UPLOADED';
    case AdminVerified = 'ADMIN_VERIFIED';
    case Delivered = 'DELIVERED';
    case Withdrawn = 'WITHDRAWN';
    case SekdaReview = 'SEKDA_REVIEW';
    case AwaitingManualSignature = 'AWAITING_MANUAL_SIGNATURE';
    case ManualScanReview = 'MANUAL_SCAN_REVIEW';
    case ReadyForDelivery = 'READY_FOR_DELIVERY';
    case RevisionRequired = 'REVISION_REQUIRED';
}

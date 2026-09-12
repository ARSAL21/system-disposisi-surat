<?php

namespace App\Enums;

enum StandaloneOutgoingDraftStatus: string
{
    case Draft = 'DRAFT';
    case SectionReview = 'SECTION_REVIEW';
    case RevisionRequired = 'REVISION_REQUIRED';
    case AssistantReview = 'ASSISTANT_REVIEW';
    case AwaitingNumber = 'AWAITING_NUMBER';
    case NumberAssigned = 'NUMBER_ASSIGNED';
    case SekdaReview = 'SEKDA_REVIEW';
    case AwaitingManualSignature = 'AWAITING_MANUAL_SIGNATURE';
    case ManualScanReview = 'MANUAL_SCAN_REVIEW';
    case ReadyForDelivery = 'READY_FOR_DELIVERY';
}

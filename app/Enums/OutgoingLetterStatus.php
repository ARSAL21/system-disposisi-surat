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
}

<?php

namespace App\Enums;

enum OutgoingDeliveryMethod: string
{
    case Email = 'EMAIL';
    case Portal = 'PORTAL';
    case InPerson = 'IN_PERSON';
    case Postal = 'POSTAL';
    case Courier = 'COURIER';
    case Other = 'OTHER';
}

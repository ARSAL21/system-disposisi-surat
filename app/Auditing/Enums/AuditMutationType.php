<?php

namespace App\Auditing\Enums;

enum AuditMutationType: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Flexible = 'flexible';
}

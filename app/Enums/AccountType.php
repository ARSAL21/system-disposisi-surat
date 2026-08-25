<?php

namespace App\Enums;

enum AccountType: string
{
    case PublicAccount = 'PUBLIC';
    case InternalAccount = 'INTERNAL';
}

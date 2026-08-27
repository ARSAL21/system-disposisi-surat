<?php

namespace App\Enums;

enum PermissionName: string
{
    case ViewAuthorization = 'authorization.view';
    case ManageAuthorization = 'authorization.manage';
}

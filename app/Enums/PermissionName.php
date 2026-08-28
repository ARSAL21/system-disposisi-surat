<?php

namespace App\Enums;

enum PermissionName: string
{
    case ViewAuthorization = 'authorization.view';
    case ManageAuthorization = 'authorization.manage';
    case ManagePositionAssignments = 'position-assignments.manage';
    case ViewPrivilegeAudits = 'privilege-audits.view';
}

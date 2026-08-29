<?php

namespace App\Enums;

enum PermissionName: string
{
    case ViewAuthorization = 'authorization.view';
    case ManageAuthorization = 'authorization.manage';
    case ViewOrganization = 'organization.view';
    case ManageOrganization = 'organization.manage';
    case ManagePositionAssignments = 'position-assignments.manage';
    case ViewPrivilegeAudits = 'privilege-audits.view';
    case ViewIntake = 'intake.view';
    case ScreenIntake = 'intake.screen';
    case DecideIntake = 'intake.decide';
}

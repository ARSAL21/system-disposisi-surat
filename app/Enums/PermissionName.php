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
    case ViewLetterActivities = 'letter-activities.view';
    case ViewDocumentVersions = 'document-versions.view';
    case CreateDocumentVersions = 'document-versions.create';
    case ViewLetterRouting = 'letter-routing.view';
    case CreateLetterRouting = 'letter-routing.create';
    case ViewExecutiveInbox = 'executive-inbox.view';
    case ViewDispositions = 'dispositions.view';
    case CreateDispositions = 'dispositions.create';
    case ViewDispositionInstructions = 'disposition-instructions.view';
    case ManageDispositionInstructions = 'disposition-instructions.manage';
    case ViewIntake = 'intake.view';
    case ScreenIntake = 'intake.screen';
    case DecideIntake = 'intake.decide';
}

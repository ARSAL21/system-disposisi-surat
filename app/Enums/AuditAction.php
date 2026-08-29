<?php

namespace App\Enums;

enum AuditAction: string
{
    case InternalAccountProvisioned = 'INTERNAL_ACCOUNT_PROVISIONED';
    case RoleChanged = 'ROLE_CHANGED';
    case PermissionChanged = 'PERMISSION_CHANGED';
    case SubmissionCreated = 'SUBMISSION_CREATED';
    case SubmissionUpdated = 'SUBMISSION_UPDATED';
    case SubmissionDocumentReplaced = 'SUBMISSION_DOCUMENT_REPLACED';
    case SubmissionSubmitted = 'SUBMISSION_SUBMITTED';
    case SubmissionDraftDeleted = 'SUBMISSION_DRAFT_DELETED';
    case PositionAssigned = 'POSITION_ASSIGNED';
    case PositionHolderReplaced = 'POSITION_HOLDER_REPLACED';
    case PositionAssignmentEnded = 'POSITION_ASSIGNMENT_ENDED';
    case PositionLevelCatalogSynchronized = 'POSITION_LEVEL_CATALOG_SYNCHRONIZED';
    case OrganizationalUnitCreated = 'ORGANIZATIONAL_UNIT_CREATED';
    case OrganizationalUnitUpdated = 'ORGANIZATIONAL_UNIT_UPDATED';
    case OrganizationalUnitStatusChanged = 'ORGANIZATIONAL_UNIT_STATUS_CHANGED';
    case PositionCreated = 'POSITION_CREATED';
    case PositionUpdated = 'POSITION_UPDATED';
    case PositionStatusChanged = 'POSITION_STATUS_CHANGED';
}

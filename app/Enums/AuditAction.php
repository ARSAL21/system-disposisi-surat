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
    case SubmissionResubmitted = 'SUBMISSION_RESUBMITTED';
    case SubmissionRevisionRequested = 'SUBMISSION_REVISION_REQUESTED';
    case SubmissionReadyForApproval = 'SUBMISSION_READY_FOR_APPROVAL';
    case SubmissionReturnedToStaff = 'SUBMISSION_RETURNED_TO_STAFF';
    case SubmissionRejected = 'SUBMISSION_REJECTED';
    case SubmissionDraftDeleted = 'SUBMISSION_DRAFT_DELETED';
    case LetterRegistered = 'LETTER_REGISTERED';
    case DocumentVersionCreated = 'DOCUMENT_VERSION_CREATED';
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

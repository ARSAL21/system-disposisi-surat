<?php

namespace App\Enums;

enum AuditAction: string
{
    case SubmissionCreated = 'SUBMISSION_CREATED';
    case SubmissionUpdated = 'SUBMISSION_UPDATED';
    case SubmissionDocumentReplaced = 'SUBMISSION_DOCUMENT_REPLACED';
    case SubmissionSubmitted = 'SUBMISSION_SUBMITTED';
    case SubmissionDraftDeleted = 'SUBMISSION_DRAFT_DELETED';
    case PositionAssigned = 'POSITION_ASSIGNED';
    case PositionHolderReplaced = 'POSITION_HOLDER_REPLACED';
    case PositionAssignmentEnded = 'POSITION_ASSIGNMENT_ENDED';
}

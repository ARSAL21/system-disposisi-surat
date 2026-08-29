import type {
    IntakeDocument,
    IntakeSubmissionSource,
    IntakeSubmissionStatus,
    IntakeTimelineItem,
    ScreeningChecklistItem,
} from './intake';

export type ApprovalQueueTab = 'pending' | 'history';

export type ApprovalDecisionOutcome =
    'INTERNAL_REVISION_REQUIRED' | 'REJECTED' | 'REGISTERED';

export type SenderOrganizationOption = {
    id: number;
    name: string;
    address: string | null;
    contact: string | null;
};

export type StaffScreeningReview = {
    checklist: ScreeningChecklistItem[];
    note: string | null;
    reviewed_by: string;
    reviewed_at: string;
};

export type ApprovalDecision = {
    outcome: ApprovalDecisionOutcome;
    note: string | null;
    decided_by: string;
    decided_at: string;
};

export type IncomingLetterRegistration = {
    agenda_number: string;
    agenda_year: number;
    sender_organization_name: string;
    registered_at: string;
};

export type ApprovalSubmission = {
    public_id: string;
    source: IntakeSubmissionSource;
    status: IntakeSubmissionStatus;
    sender_organization_name: string;
    contact_name: string;
    contact_email: string;
    contact_phone: string | null;
    external_letter_number: string | null;
    external_letter_date: string | null;
    subject: string;
    summary: string | null;
    submitted_at: string;
    document: IntakeDocument | null;
    screening_review: StaffScreeningReview;
    latest_decision: ApprovalDecision | null;
    registration: IncomingLetterRegistration | null;
    timeline: IntakeTimelineItem[];
    capabilities: {
        can_decide: boolean;
        can_download_document: boolean;
    };
    links: {
        show: string;
        decision: string;
        document_preview: string | null;
        document_download: string | null;
    };
};

export type ApprovalFilters = {
    tab: ApprovalQueueTab;
    search: string;
    date_from: string;
    date_to: string;
};

export type ApprovalSummary = {
    awaiting_decision: number;
    returned_to_staff: number;
    registered: number;
    rejected: number;
};

export type ApprovalPagination = {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    previous_url: string | null;
    next_url: string | null;
};

export type PaginatedApprovalSubmissions = {
    data: ApprovalSubmission[];
    pagination: ApprovalPagination;
};

export type ReturnApprovalPayload = {
    outcome: 'INTERNAL_REVISION_REQUIRED';
    note: string;
};

export type RejectApprovalPayload = {
    outcome: 'REJECTED';
    note: string;
};

export type RegisterApprovalPayload = {
    outcome: 'REGISTERED';
    agenda_number: string;
    note: string | null;
    sender_organization:
        | { mode: 'existing'; id: number }
        | {
              mode: 'new';
              name: string;
              address: string | null;
              contact: string | null;
          };
};

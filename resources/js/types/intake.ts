export type IntakeSubmissionStatus =
    | 'SUBMITTED'
    | 'REVISION_REQUIRED'
    | 'READY_FOR_APPROVAL'
    | 'INTERNAL_REVISION_REQUIRED'
    | 'REGISTERED'
    | 'REJECTED';

export type IntakeSubmissionSource = 'ONLINE' | 'MANUAL';

export type IntakeReviewOutcome = 'REVISION_REQUIRED' | 'READY_FOR_APPROVAL';

export type IntakeDocument = {
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    sha256: string;
    uploaded_at: string | null;
};

export type IntakeTimelineItem = {
    id: string;
    title: string;
    description: string;
    occurred_at: string | null;
    state: 'complete' | 'current' | 'upcoming';
};

export type ScreeningChecklistItem = {
    id: string;
    label: string;
    description: string;
    checked: boolean;
};

export type IntakeSubmission = {
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
    submitted_at: string | null;
    created_at: string | null;
    document: IntakeDocument | null;
    timeline: IntakeTimelineItem[];
    checklist: ScreeningChecklistItem[];
    latest_note: string | null;
    internal_revision_note: string | null;
    capabilities: {
        can_screen: boolean;
        can_download_document: boolean;
    };
    links: {
        show: string;
        screen: string;
        document_preview: string | null;
        document_download: string | null;
    };
};

export type IntakeFilters = {
    search: string;
    status: IntakeSubmissionStatus | 'all' | 'action_required';
    source: IntakeSubmissionSource | 'all';
    date_from: string;
    date_to: string;
};

export type IntakeSummary = {
    awaiting_screening: number;
    returned_to_staff: number;
    revision_required: number;
    ready_for_approval: number;
    processed_today: number;
};

export type IntakePagination = {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    previous_url: string | null;
    next_url: string | null;
};

export type PaginatedIntakeSubmissions = {
    data: IntakeSubmission[];
    links: {
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        to: number | null;
        total: number;
    };
};

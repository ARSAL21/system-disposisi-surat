export type SubmissionStatus =
    | 'DRAFT'
    | 'SUBMITTED'
    | 'REVISION_REQUIRED'
    | 'READY_FOR_APPROVAL'
    | 'INTERNAL_REVISION_REQUIRED'
    | 'REGISTERED'
    | 'REJECTED';

export type SubmissionDocument = {
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    uploaded_at: string | null;
};

export type SubmissionCapabilities = {
    can_update: boolean;
    can_replace_document: boolean;
    can_submit: boolean;
    can_delete: boolean;
    can_download_document: boolean;
};

export type LetterSubmission = {
    public_id: string;
    source: 'ONLINE' | 'MANUAL';
    status: SubmissionStatus;
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
    updated_at: string | null;
    revision_note: string | null;
    rejection_note: string | null;
    document: SubmissionDocument | null;
    capabilities: SubmissionCapabilities;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedSubmissions = {
    data: LetterSubmission[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type PublicDashboardSummary = {
    total: number;
    draft: number;
    submitted: number;
};

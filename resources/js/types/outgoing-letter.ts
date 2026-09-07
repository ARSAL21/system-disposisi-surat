export type OutgoingLetterStatus =
    | 'AUTHORIZED'
    | 'NUMBER_ASSIGNED'
    | 'SIGNED_DOCUMENT_UPLOADED'
    | 'ADMIN_VERIFIED'
    | 'DELIVERED'
    | 'WITHDRAWN';

export type OutgoingLetterSource = 'ONLINE' | 'MANUAL';

export type OutgoingDeliveryMethod =
    | 'PORTAL'
    | 'IN_PERSON'
    | 'POSTAL'
    | 'COURIER'
    | 'OTHER';

export type OutgoingLetterFilters = {
    search: string;
    status: '' | OutgoingLetterStatus;
    source: '' | OutgoingLetterSource;
    year: string;
};

export type OutgoingLetterSummary = {
    total: number;
    awaiting_number: number;
    awaiting_verification: number;
    ready_for_delivery: number;
    delivered_this_month: number;
};

export type OutgoingLetterListItem = {
    public_id: string;
    subject: string;
    status: OutgoingLetterStatus;
    source: OutgoingLetterSource;
    incoming_agenda_number: string;
    sender_organization_name: string;
    outgoing_number: string | null;
    letter_date: string | null;
    signatory_position: string;
    signatory_name: string | null;
    authorized_at: string;
    updated_at: string;
    next_action_label: string | null;
    links: {
        detail: string;
    };
};

export type OutgoingLetterPagination = {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    previous_url: string | null;
    next_url: string | null;
};

export type OutgoingLetterDocumentVersion = {
    public_id: string;
    version_number: number;
    original_filename: string;
    mime_type: 'application/pdf';
    size_bytes: number;
    sha256_fingerprint: string;
    uploaded_by: string;
    uploaded_at: string;
    upload_note: string | null;
    review_status: 'PENDING' | 'VERIFIED' | 'RETURNED';
    review_note: string | null;
    links: {
        preview: string | null;
        download: string | null;
    };
};

export type OutgoingLetterHistoryEntry = {
    key: string;
    label: string;
    description: string;
    actor_name: string;
    actor_position: string;
    occurred_at: string;
    state: 'DONE' | 'CURRENT' | 'STOPPED';
};

export type OutgoingLetterDetail = {
    public_id: string;
    subject: string;
    status: OutgoingLetterStatus;
    source: OutgoingLetterSource;
    incoming_letter: {
        reference: string;
        agenda_number: string;
        subject: string;
        sender_organization_name: string;
        received_at: string;
        dossier_url: string | null;
    };
    mandate: {
        source_document_title: string;
        source_version_number: number;
        authorized_by: string;
        authorized_position: string;
        authorized_at: string;
    };
    signatory: {
        position_name: string;
        official_name: string | null;
    };
    numbering: {
        outgoing_number: string | null;
        agenda_year: number | null;
        letter_date: string | null;
        numbered_by: string | null;
        numbered_at: string | null;
    };
    documents: OutgoingLetterDocumentVersion[];
    verification: {
        verified_by: string | null;
        verified_position: string | null;
        verified_at: string | null;
        note: string | null;
    };
    delivery: {
        method: OutgoingDeliveryMethod | null;
        recipient_name: string | null;
        delivered_by: string | null;
        delivered_at: string | null;
        tracking_number: string | null;
        note: string | null;
    };
    withdrawal: {
        reason: string;
        withdrawn_by: string;
        withdrawn_at: string;
    } | null;
    history: OutgoingLetterHistoryEntry[];
    capabilities: {
        can_assign_number: boolean;
        can_upload_signed_document: boolean;
        can_verify: boolean;
        can_request_document_revision: boolean;
        can_deliver: boolean;
        can_withdraw: boolean;
    };
    routes: {
        index: string;
        assign_number: string | null;
        upload_signed_document: string | null;
        verify: string | null;
        request_document_revision: string | null;
        deliver: string | null;
        withdraw: string | null;
    };
};

export type OutgoingRegisterPageProps = {
    letters?: {
        data: OutgoingLetterListItem[];
        pagination: OutgoingLetterPagination;
    };
    summary?: OutgoingLetterSummary;
    filters?: OutgoingLetterFilters;
    routes?: {
        index: string;
    };
    preview?: boolean;
};

export type OutgoingLetterDetailPageProps = {
    outgoingLetter?: OutgoingLetterDetail;
    preview?: boolean;
};

export type OutgoingLetterUiAction =
    | { kind: 'assign_number'; route: string }
    | { kind: 'upload_signed_document'; route: string }
    | { kind: 'verify'; route: string }
    | { kind: 'request_document_revision'; route: string }
    | { kind: 'deliver'; route: string; source: OutgoingLetterSource }
    | { kind: 'withdraw'; route: string };

export type PublicResponseStatus =
    | 'IN_PROCESS'
    | 'PREPARING_RESPONSE'
    | 'RESPONSE_AVAILABLE';

export type PublicOfficialResponse = {
    public_id: string;
    outgoing_number: string;
    letter_date: string;
    subject: string;
    signatory_position: string;
    delivered_at: string;
    preview_url: string;
    download_url: string;
};

export type PublicResponseTracker = {
    status: PublicResponseStatus;
    updated_at: string;
    responses: PublicOfficialResponse[];
};

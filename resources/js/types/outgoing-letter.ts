export type OutgoingLetterStatus =
    | 'AUTHORIZED'
    | 'NUMBER_ASSIGNED'
    | 'SIGNED_DOCUMENT_UPLOADED'
    | 'ADMIN_VERIFIED'
    | 'DELIVERED'
    | 'WITHDRAWN'
    | 'SEKDA_REVIEW'
    | 'AWAITING_MANUAL_SIGNATURE'
    | 'MANUAL_SCAN_REVIEW'
    | 'READY_FOR_DELIVERY'
    | 'REVISION_REQUIRED';

export type OutgoingLetterOrigin = 'RESPONSE' | 'STANDALONE';

export type OutgoingLetterSource = 'ONLINE' | 'MANUAL';

export type OutgoingDeliveryMethod =
    'EMAIL' | 'PORTAL' | 'IN_PERSON' | 'POSTAL' | 'COURIER' | 'OTHER';

export type OutgoingLetterFilters = {
    search: string;
    status: '' | OutgoingLetterStatus;
    source: '' | OutgoingLetterSource;
    origin: '' | OutgoingLetterOrigin;
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
    origin: OutgoingLetterOrigin;
    source: OutgoingLetterSource | null;
    incoming_agenda_number: string | null;
    sender_organization_name: string | null;
    originating_unit_name: string | null;
    outgoing_number: string | null;
    letter_date: string | null;
    signatory_position: string;
    signatory_name: string | null;
    authorized_at: string;
    updated_at: string;
    next_action_label: string | null;
    links: {
        detail: string;
        sekda_approval: string | null;
    };
};

export type StandaloneNumberQueueItem = {
    public_id: string;
    draft_public_id: string;
    subject: string;
    recipient_name: string;
    recipient_organization: string | null;
    originating_unit_name: string;
    current_version_number: number;
    sha256_fingerprint: string;
    approved_at: string;
    assign_number_url: string | null;
};

export type SekdaApprovalQueueItem = {
    public_id: string;
    subject: string;
    outgoing_number: string;
    letter_date: string;
    originating_unit_name: string;
    recipient_name: string;
    sha256_fingerprint: string;
    approval_url: string | null;
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
    origin: OutgoingLetterOrigin;
    source: OutgoingLetterSource | null;
    incoming_letter: {
        reference: string;
        agenda_number: string;
        subject: string;
        sender_organization_name: string;
        received_at: string;
        dossier_url: string | null;
    } | null;
    standalone_draft: {
        public_id: string;
        originating_unit_name: string;
        recipient_name: string;
        recipient_organization: string | null;
        recipient_position: string | null;
        copy_recipients: string[];
        template_name: string;
        template_version_number: number;
        concept_version_number: number;
        concept_sha256_fingerprint: string;
    } | null;
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
        recipient_email?: string | null;
        recipient_name: string | null;
        delivered_by: string | null;
        delivered_at: string | null;
        tracking_number: string | null;
        note: string | null;
        email?: {
            status: 'NOT_SENT' | 'SENT' | 'EXPIRED' | 'REVOKED';
            sent_at: string | null;
            expires_at: string | null;
            download_url_status: string;
            resend_url: string | null;
            revoke_url: string | null;
        } | null;
    };
    internal_copies?: Array<{
        name: string;
        position: string;
        notified_at: string | null;
        acknowledged: boolean;
    }>;
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
        can_select_sekda_approval: boolean;
        can_upload_manual_scan: boolean;
        can_review_manual_scan: boolean;
        can_return_for_revision: boolean;
        can_create_correction?: boolean;
        can_resend_delivery_email?: boolean;
        can_revoke_delivery_email?: boolean;
    };
    routes: {
        index: string;
        assign_number: string | null;
        upload_signed_document: string | null;
        verify: string | null;
        request_document_revision: string | null;
        deliver: string | null;
        withdraw: string | null;
        sekda_approval: string | null;
        approve_qr: string | null;
        choose_manual_signature: string | null;
        upload_manual_scan: string | null;
        review_manual_scan: string | null;
        return_for_revision: string | null;
        create_correction?: string | null;
        resend_delivery_email?: string | null;
        revoke_delivery_email?: string | null;
    };
};

export type SekdaApprovalDetail = {
    public_id: string;
    subject: string;
    status: Extract<
        OutgoingLetterStatus,
        | 'SEKDA_REVIEW'
        | 'AWAITING_MANUAL_SIGNATURE'
        | 'MANUAL_SCAN_REVIEW'
        | 'READY_FOR_DELIVERY'
        | 'REVISION_REQUIRED'
    >;
    outgoing_number: string;
    letter_date: string;
    originating_unit_name: string;
    recipient: {
        name: string;
        organization: string | null;
        position: string | null;
    };
    copy_recipients: string[];
    current_document: {
        version_number: number;
        sha256_fingerprint: string;
        preview_url: string | null;
        download_url: string | null;
    } | null;
    qr_placement: {
        page_label: string;
        x_ratio: number;
        y_ratio: number;
        width_ratio: number;
        height_ratio: number;
    };
    manual_scan: {
        version_number: number;
        sha256_fingerprint: string;
        uploaded_at: string;
        uploaded_by: string;
        preview_url: string | null;
    } | null;
    review: {
        decision: 'VERIFIED' | 'RETURNED';
        note: string | null;
        reviewed_by: string;
        reviewed_at: string;
    } | null;
    capabilities: {
        can_approve_qr: boolean;
        can_choose_manual_signature: boolean;
        can_upload_manual_scan: boolean;
        can_review_manual_scan: boolean;
        can_return_for_revision: boolean;
    };
    routes: {
        index: string;
        approve_qr: string | null;
        choose_manual_signature: string | null;
        upload_manual_scan: string | null;
        review_manual_scan: string | null;
        return_for_revision: string | null;
    };
};

export type OutgoingRegisterPageProps = {
    letters?: {
        data: OutgoingLetterListItem[];
        pagination: OutgoingLetterPagination;
    };
    summary?: OutgoingLetterSummary;
    numbering_queue?: StandaloneNumberQueueItem[];
    sekda_approval_queue?: SekdaApprovalQueueItem[];
    filters?: OutgoingLetterFilters;
    routes?: {
        index: string;
        sekda_approval_index?: string;
    };
    preview?: boolean;
};

export type OutgoingLetterDetailPageProps = {
    outgoingLetter?: OutgoingLetterDetail;
    preview?: boolean;
};

export type SekdaApprovalPageProps = {
    approval?: SekdaApprovalDetail;
    preview?: boolean;
};

export type OutgoingLetterUiAction =
    | { kind: 'assign_number'; route: string }
    | { kind: 'upload_signed_document'; route: string }
    | { kind: 'verify'; route: string }
    | { kind: 'request_document_revision'; route: string }
    | { kind: 'deliver'; route: string; source: OutgoingLetterSource }
    | { kind: 'withdraw'; route: string }
    | { kind: 'create_correction'; route: string };

export type PublicResponseStatus =
    'IN_PROCESS' | 'PREPARING_RESPONSE' | 'RESPONSE_AVAILABLE';

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

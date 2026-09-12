export type StandaloneOutgoingStatus =
    | 'DRAFT'
    | 'SECTION_REVIEW'
    | 'REVISION_REQUIRED'
    | 'ASSISTANT_REVIEW'
    | 'AWAITING_NUMBER';

export type StandaloneOutgoingUnit = { code: string; name: string };

export type StandaloneOutgoingTemplateVersion = {
    public_id: string;
    version_number: number;
    original_filename: string;
    size_bytes: number;
    sha256: string;
    qr_placement: {
        page_mode: 'LAST_PAGE' | 'SPECIFIC_PAGE';
        page_number: number | null;
        x_ratio: number;
        y_ratio: number;
        width_ratio: number;
        height_ratio: number;
    };
    uploaded_by: { name: string };
    created_at: string | null;
    links: { download: string };
};

export type OutgoingTemplate = {
    public_id: string;
    code: string;
    name: string;
    is_active: boolean;
    can_manage: boolean;
    unit: StandaloneOutgoingUnit;
    latest_version: StandaloneOutgoingTemplateVersion | null;
    versions: StandaloneOutgoingTemplateVersion[];
    links: { version_store: string | null; status_update: string | null };
};

export type StandaloneOutgoingFormOptions = {
    units: StandaloneOutgoingUnit[];
    templates: Array<{
        unit_code: string | null;
        code: string;
        name: string;
        latest_version: { public_id: string; version_number: number } | null;
    }>;
    copy_positions: Array<{
        code: string;
        name: string;
        unit_name: string | null;
    }>;
};

export type StandaloneOutgoingDraft = {
    public_id: string;
    unit: StandaloneOutgoingUnit;
    recipient_name: string;
    recipient_organization: string | null;
    subject: string;
    status: StandaloneOutgoingStatus;
    status_label: string;
    current_version_number: number | null;
    updated_at: string | null;
    created_by: { name: string };
    links: { show: string };
    can_edit: boolean;
    can_review_section: boolean;
    can_review_assistant: boolean;
};

export type StandaloneOutgoingDraftDetail = StandaloneOutgoingDraft & {
    recipient: {
        name: string;
        organization: string | null;
        position: string | null;
        address: string | null;
        email: string | null;
    };
    summary: string | null;
    template_version_public_id: string;
    template: { code: string; name: string; version_number: number };
    copy_recipients: Array<{
        code: string;
        name: string;
        unit_name: string | null;
    }>;
    document_versions: Array<{
        public_id: string;
        version_number: number;
        original_filename: string;
        mime_type: string;
        size_bytes: number;
        sha256: string;
        revision_note: string | null;
        uploaded_by: { name: string };
        created_at: string | null;
        links: { preview: string; download: string };
    }>;
    reviews: Array<{
        stage: 'SECTION_HEAD' | 'ASSISTANT';
        stage_label: string;
        decision: 'APPROVED' | 'RETURNED';
        decision_label: string;
        reason: string | null;
        decided_by: { name: string };
        created_at: string | null;
    }>;
    actions: {
        update: string | null;
        submit: string | null;
        document_version: string | null;
        section_review: string | null;
        assistant_review: string | null;
    };
};

export type StandaloneOutgoingPageProps = {
    drafts: {
        data: StandaloneOutgoingDraft[];
        links?: unknown;
        meta?: unknown;
    };
    form_options: StandaloneOutgoingFormOptions;
    routes: { store: string };
};

export type StandaloneOutgoingShowPageProps = {
    draft: StandaloneOutgoingDraftDetail;
    form_options: StandaloneOutgoingFormOptions;
};

export type OutgoingTemplatesPageProps = {
    templates: OutgoingTemplate[];
    units: StandaloneOutgoingUnit[];
    routes: { store: string };
};

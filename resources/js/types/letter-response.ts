export type LetterResponseRole =
    | 'EXECUTIVE'
    | 'ASSISTANT'
    | 'SECTION_HEAD';

export type LetterResponseSource = 'ONLINE' | 'MANUAL';

export type LetterResponseDocumentKind =
    | 'TECHNICAL_MATERIAL'
    | 'ASSISTANT_PROPOSAL'
    | 'EXECUTIVE_CONSOLIDATION';

export type LetterResponseStatus =
    | 'PENDING'
    | 'IN_PROGRESS'
    | 'COMPLETED'
    | 'READY'
    | 'REVISION_REQUIRED'
    | 'AUTHORIZED'
    | 'NUMBER_ASSIGNED'
    | 'SIGNED_DOCUMENT_UPLOADED'
    | 'ADMIN_VERIFIED'
    | 'DELIVERED'
    | 'WITHDRAWN'
    | 'FULFILLED';

export type LetterResponseDocumentVersion = {
    public_id: string;
    version_number: number;
    original_filename: string;
    mime_type: 'application/pdf';
    size_bytes: number;
    sha256_fingerprint: string;
    uploaded_by: string;
    uploaded_at: string;
    revision_note: string | null;
    links: {
        preview: string | null;
        download: string | null;
    };
};

export type LetterResponseDocumentSeries = {
    public_id: string;
    kind: LetterResponseDocumentKind;
    title: string;
    status: 'READY' | 'REVISION_REQUIRED';
    current_version: LetterResponseDocumentVersion | null;
    versions: LetterResponseDocumentVersion[];
    can_upload: boolean;
    can_return: boolean;
    can_select: boolean;
    open_revision_reason: string | null;
    routes?: {
        revision_store: string;
        return: string;
    };
};

export type LetterResponseSectionBranch = {
    public_id: string;
    position_name: string;
    unit_name: string;
    official_name: string | null;
    status: Extract<LetterResponseStatus, 'PENDING' | 'IN_PROGRESS' | 'COMPLETED'>;
    received_at: string;
    started_at: string | null;
    completed_at: string | null;
    completion_note: string | null;
    material: LetterResponseDocumentSeries | null;
    can_upload_material: boolean;
    routes?: { material_store: string };
};

export type LetterResponseAssistantBranch = {
    public_id: string;
    position_name: string;
    unit_name: string;
    official_name: string | null;
    status: Extract<LetterResponseStatus, 'PENDING' | 'COMPLETED'>;
    forwarded_at: string | null;
    child_progress: {
        total: number;
        completed: number;
        percent: number;
    };
    children: LetterResponseSectionBranch[];
    proposal: LetterResponseDocumentSeries | null;
    can_submit_proposal: boolean;
    can_return_material: boolean;
    routes?: { proposal_store: string };
};

export type LetterResponseMandate = {
    public_id: string;
    subject: string;
    version_number: number;
    signatory_name: string;
    signatory_position: string;
    status: 'AUTHORIZED' | 'NUMBER_ASSIGNED' | 'SIGNED_DOCUMENT_UPLOADED' | 'ADMIN_VERIFIED' | 'DELIVERED' | 'WITHDRAWN';
    created_at: string;
};

export type LetterResponseDossier = {
    public_id: string;
    status: 'OPEN' | 'FINALIZED';
    opened_at: string;
    finalized_at: string | null;
    letter: {
        reference: string;
        agenda_number: string;
        subject: string;
        sender_organization_name: string;
        source: LetterResponseSource;
        status: 'IN_PROGRESS' | 'COMPLETED';
        received_at: string;
    };
    executive: {
        position_name: string;
        official_name: string | null;
        role: 'EXECUTIVE';
    };
    assistants: LetterResponseAssistantBranch[];
    consolidation?: LetterResponseDocumentSeries | null;
    progress: {
        total: number;
        completed: number;
        percent: number;
    };
    mandates: LetterResponseMandate[];
    eligible_signatories?: Array<{
        code: string;
        name: string;
        official_name: string | null;
    }>;
    viewer: {
        role: LetterResponseRole;
        display_name: string;
        can_view: boolean;
        can_contribute: boolean;
        can_review: boolean;
        can_authorize: boolean;
    };
    links: {
        index: string;
        finalize: string | null;
        consolidation_store?: string | null;
        mandate_store?: string | null;
    };
};

export type LetterResponseListItem = Pick<
    LetterResponseDossier,
    'public_id' | 'status' | 'letter' | 'progress' | 'mandates'
> & {
    assistants_count: number;
    updated_at: string;
    links: { detail: string };
};

export type LetterResponseRoutes = {
    index: string;
};

export type LetterResponsePageProps = {
    dossiers?: LetterResponseListItem[];
    dossier?: LetterResponseDossier | null;
    routes?: LetterResponseRoutes;
    preview?: boolean;
};

export type LetterResponseSelection =
    | { kind: 'executive'; dossier: LetterResponseDossier }
    | { kind: 'assistant'; branch: LetterResponseAssistantBranch }
    | { kind: 'section'; branch: LetterResponseSectionBranch };

export type LetterResponseUiAction =
    | {
          kind: 'upload';
          route: string;
          title: string;
          description: string;
          sourceVersionPublicIds?: string[];
      }
    | {
          kind: 'return';
          route: string;
          title: string;
          description: string;
      }
    | {
          kind: 'mandate';
          route: string;
          title: string;
          description: string;
          sources: Array<{ value: string; label: string }>;
          signatories: Array<{ value: string; label: string }>;
      }
    | {
          kind: 'finalize';
          route: string;
          title: string;
          description: string;
      };

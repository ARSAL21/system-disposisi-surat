export type DocumentVersionLetter = {
    id: number;
    agenda_number: string;
    agenda_year: number;
    subject: string;
    status: string;
    received_at?: string | null;
};

export type DocumentVersionUploader = {
    id: number;
    name: string;
    position?: string | null;
    unit?: string | null;
};

export type DocumentVersionItem = {
    id: number;
    version_number: number;
    is_current: boolean;
    replaces_version_number: number | null;
    source:
        'ONLINE_SUBMISSION' | 'MANUAL_CORRECTION' | 'MANUAL_INTAKE' | string;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    sha256: string;
    correction_reason: string | null;
    uploaded_by: DocumentVersionUploader;
    recorded_by?: DocumentVersionUploader | null;
    created_at: string;
    preview_url: string;
    download_url: string;
};

export type DocumentVersionCapabilities = {
    can_create_version: boolean;
};

export type DocumentVersionHistoryResponse = {
    letter: DocumentVersionLetter;
    versions: DocumentVersionItem[];
    capabilities: DocumentVersionCapabilities;
    next_version_number: number;
    routes: DocumentVersionRoutes;
};

export type DocumentVersionRoutes = {
    archive: string;
    store: string;
};

export type CreateDocumentVersionPayload = {
    document: File | null;
    correction_reason: string;
};

export type DocumentArchiveStatus =
    'REGISTERED' | 'ROUTED' | 'IN_PROGRESS' | 'COMPLETED';

export type DocumentArchiveFilters = {
    search: string;
    status: '' | DocumentArchiveStatus;
    date_from: string;
    date_to: string;
};

export type DocumentArchiveVersionSummary = {
    version_number: number;
    original_filename: string;
    size_bytes: number;
    sha256: string;
    created_at: string;
};

export type DocumentArchiveItem = {
    id: number;
    agenda_number: string;
    agenda_year: number;
    subject: string;
    sender_organization_name: string;
    received_at: string;
    status: DocumentArchiveStatus;
    total_versions: number;
    current_version: DocumentArchiveVersionSummary;
    links: {
        history: string;
    };
};

export type DocumentArchiveSummary = {
    total_letters: number;
    corrected_letters: number;
    total_versions: number;
    updated_this_month: number;
};

export type DocumentArchivePagination = {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    previous_url: string | null;
    next_url: string | null;
};

export type PaginatedDocumentArchive = {
    data: DocumentArchiveItem[];
    pagination: DocumentArchivePagination;
};

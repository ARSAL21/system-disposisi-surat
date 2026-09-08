export type IncomingRegisterStatus =
    'REGISTERED' | 'ROUTED' | 'IN_PROGRESS' | 'COMPLETED';

export type IncomingRegisterSource = 'ONLINE' | 'MANUAL';

export type IncomingRegisterFilters = {
    search: string;
    source: '' | IncomingRegisterSource;
    status: '' | IncomingRegisterStatus;
    year: string;
    date_from: string;
    date_to: string;
};

export type IncomingRegisterDocument = {
    original_filename: string;
    size_bytes: number;
};

export type IncomingRegisterItem = {
    id: number;
    agenda_number: string;
    agenda_year: number;
    source: IncomingRegisterSource;
    received_at: string;
    sender_organization_name: string;
    contact_name: string;
    external_letter_number: string | null;
    external_letter_date: string | null;
    subject: string;
    status: IncomingRegisterStatus;
    document: IncomingRegisterDocument | null;
    links: {
        document_history: string | null;
    };
};

export type IncomingRegisterSummary = {
    total_letters: number;
    online_letters: number;
    manual_letters: number;
    received_today: number;
};

export type IncomingRegisterPagination = {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    previous_url: string | null;
    next_url: string | null;
};

export type PaginatedIncomingRegister = {
    data: IncomingRegisterItem[];
    pagination: IncomingRegisterPagination;
};

export type IncomingRegisterRoutes = {
    index: string;
    create_manual: string | null;
};

export type IncomingRegisterPageProps = {
    letters?: PaginatedIncomingRegister;
    summary?: IncomingRegisterSummary;
    filters?: IncomingRegisterFilters;
    routes?: IncomingRegisterRoutes;
    preview?: boolean;
};

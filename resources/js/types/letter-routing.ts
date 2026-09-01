export type LetterRoutingStatus = 'REGISTERED' | 'ROUTED';

export type InitialRouteStatus = 'PENDING' | 'COMPLETED';

export type LetterRoutingFilters = {
    search: string;
    status: '' | LetterRoutingStatus;
};

export type ExecutiveInboxFilters = {
    search: string;
    date_from: string;
    date_to: string;
};

export type ExecutivePositionOption = {
    id: number;
    code: string;
    name: string;
    holder_name: string | null;
    is_available: boolean;
};

export type RoutingActor = {
    name: string;
    position: string;
    unit: string | null;
};

export type RoutingOfficialDocument = {
    version_number: number;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    sha256: string;
    recorded_at: string;
    preview_url: string;
    download_url: string;
};

export type InitialRouteReceipt = {
    status: InitialRouteStatus;
    target_position: ExecutivePositionOption;
    routed_by: RoutingActor;
    routed_at: string;
};

export type LetterRoutingItem = {
    id: number;
    agenda_number: string;
    agenda_year: number;
    subject: string;
    sender_organization_name: string;
    external_letter_number: string | null;
    received_at: string;
    status: LetterRoutingStatus;
    current_document: RoutingOfficialDocument;
    current_route: InitialRouteReceipt | null;
    links: {
        show: string;
    };
};

export type LetterRoutingSummary = {
    awaiting_route: number;
    pending_executive: number;
    routed_today: number;
};

export type RoutingPagination = {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    previous_url: string | null;
    next_url: string | null;
};

export type PaginatedLetterRouting = {
    data: LetterRoutingItem[];
    pagination: RoutingPagination;
};

export type LetterRoutingCapabilities = {
    can_route: boolean;
};

export type LetterRoutingRoutes = {
    index: string;
    store: string;
};

export type ExecutiveInboxItem = {
    route_id: number;
    letter: LetterRoutingItem;
    received_in_inbox_at: string;
    links: {
        show: string;
    };
};

export type ExecutiveInboxSummary = {
    pending: number;
    received_today: number;
};

export type PaginatedExecutiveInbox = {
    data: ExecutiveInboxItem[];
    pagination: RoutingPagination;
};

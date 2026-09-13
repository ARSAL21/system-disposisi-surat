import type {
    ExecutivePositionOption,
    LetterRoutingItem,
    RoutingActor,
} from './letter-routing';

export type ExpertConsultationStatus = 'PENDING' | 'REPORTED' | 'CANCELLED';

export type ExpertAdvisorOption = ExecutivePositionOption & {
    field: string;
    is_available: boolean;
};

export type ExpertConsultationReport = {
    summary: string;
    recommendation: string;
    reported_at: string;
    reported_by: RoutingActor;
    document: {
        original_filename: string;
        mime_type: string;
        size_bytes: number;
        sha256: string;
        preview_url: string;
        download_url: string;
    } | null;
};

export type ExpertConsultation = {
    id: number;
    letter: LetterRoutingItem;
    advisor: ExpertAdvisorOption;
    status: ExpertConsultationStatus;
    request_note: string | null;
    requested_at: string;
    report: ExpertConsultationReport | null;
    links: {
        show: string;
        report?: string;
        cancel?: string;
    };
};

export type ExpertConsultationRoutes = {
    index: string;
    store?: string;
    coordination?: string;
};

export type ExpertConsultationCapabilities = {
    can_view: boolean;
    can_request: boolean;
    can_respond: boolean;
    can_coordinate: boolean;
};

export type ExpertConsultationFilters = {
    search: string;
    status: '' | ExpertConsultationStatus;
};

export type ExpertConsultationPageProps = {
    consultations?: ExpertConsultation[];
    filters?: ExpertConsultationFilters;
    capabilities?: ExpertConsultationCapabilities;
    routes?: ExpertConsultationRoutes;
    preview?: boolean;
};

export type ExpertConsultationDetailProps = {
    consultation?: ExpertConsultation;
    capabilities?: ExpertConsultationCapabilities;
    routes?: ExpertConsultationRoutes;
    preview?: boolean;
};

export type RequestExpertConsultationPayload = {
    expert_position_ids: number[];
    request_note: string;
};

export type ReportExpertConsultationPayload = {
    summary: string;
    recommendation: string;
    document: File | null;
};


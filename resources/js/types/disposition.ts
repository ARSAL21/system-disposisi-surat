import type {
    LetterRoutingItem,
    RoutingActor,
    RoutingOfficialDocument,
    RoutingPagination,
} from './letter-routing';
import type { MutationSecurityState } from './organization';

export type DispositionRecipientStatus =
    'PENDING' | 'IN_PROGRESS' | 'COMPLETED';

export type DispositionPositionOption = {
    id: number;
    code: string;
    name: string;
    level_code: 'ASSISTANT' | 'SECTION_HEAD';
    unit_name: string | null;
    holder_name: string | null;
    is_available: boolean;
};

export type DispositionInstructionLabelOption = {
    id: number;
    code: string;
    name: string;
    description: string | null;
};

export type DispositionInstructionSnapshot = {
    code: string;
    name: string;
};

export type FirstDispositionReceipt = {
    status: DispositionRecipientStatus;
    recipient_position: DispositionPositionOption;
    instructions: DispositionInstructionSnapshot[];
    instruction_note: string | null;
    disposed_by: RoutingActor;
    disposed_at: string;
};

export type FirstDispositionCapabilities = {
    can_create_disposition: boolean;
};

export type FirstDispositionRoutes = {
    index: string;
    store: string;
};

export type CreateFirstDispositionPayload = {
    recipient_position_id: number;
    instruction_label_ids: number[];
    instruction_note: string;
};

export type DispositionInboxFilters = {
    search: string;
    status: '' | DispositionRecipientStatus;
    date_from: string;
    date_to: string;
};

export type DispositionInboxItem = {
    recipient_id: number;
    letter: LetterRoutingItem;
    sender: RoutingActor;
    recipient_position: DispositionPositionOption;
    instructions: DispositionInstructionSnapshot[];
    instruction_note: string | null;
    status: DispositionRecipientStatus;
    received_at: string;
    current_document: RoutingOfficialDocument;
    links: {
        show: string;
    };
};

export type DispositionInboxSummary = {
    pending: number;
    in_progress: number;
    received_today: number;
};

export type PaginatedDispositionInbox = {
    data: DispositionInboxItem[];
    pagination: RoutingPagination;
};

export type DispositionInboxRoutes = {
    index: string;
};

export type ForwardDispositionPayload = {
    recipient_position_ids: number[];
    instruction_label_ids: number[];
    instruction_note: string;
};

export type ForwardDispositionCapabilities = {
    can_forward_disposition: boolean;
};

export type ForwardedDispositionRecipient = {
    recipient_position: DispositionPositionOption;
    status: DispositionRecipientStatus;
    received_at: string;
};

export type ForwardDispositionReceipt = {
    instructions: DispositionInstructionSnapshot[];
    instruction_note: string | null;
    recipients: ForwardedDispositionRecipient[];
    disposed_by: RoutingActor;
    disposed_at: string;
};

export type DispositionInboxDetailRoutes = {
    index: string;
    store?: string;
};

export type InstructionLabelStatus = 'active' | 'inactive' | 'all';

export type InstructionLabelFilters = {
    search: string;
    status: InstructionLabelStatus;
};

export type DispositionInstructionLabel = DispositionInstructionLabelOption & {
    sort_order: number;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
    links: {
        update: string;
        status: string;
    };
};

export type InstructionLabelRoutes = {
    index: string;
    store: string;
};

export type InstructionLabelPageProps = {
    labels?: DispositionInstructionLabel[];
    activeLabelCount?: number;
    filters?: InstructionLabelFilters;
    mutationSecurity?: MutationSecurityState;
    routes?: InstructionLabelRoutes;
    preview?: boolean;
};

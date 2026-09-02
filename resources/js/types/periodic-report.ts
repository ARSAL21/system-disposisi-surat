import type { RoutingActor, RoutingPagination } from './letter-routing';

export type ReportLetterSource = 'ONLINE' | 'MANUAL';

export type ReportEventBasis = 'RECEIVED' | 'PROCESSING_STARTED' | 'COMPLETED';

export type ReportLetterStatus =
    'REGISTERED' | 'ROUTED' | 'IN_PROGRESS' | 'COMPLETED';

export type ReportScopeMode =
    | 'CITYWIDE'
    | 'GENERAL_AFFAIRS_GLOBAL'
    | 'ASSISTANT_SUBTREE'
    | 'SECTION_HEAD_BRANCHES';

export type PeriodicReportFilters = {
    date_from: string;
    date_to: string;
    source: '' | ReportLetterSource;
    event: ReportEventBasis;
    status: '' | ReportLetterStatus;
    search: string;
};

export type PeriodicReportScope = {
    mode: ReportScopeMode;
    label: string;
    description: string;
};

export type PeriodicReportSummary = {
    received_letters: number;
    processing_started: number;
    completed_letters: number;
    average_completion_hours: number | null;
};

export type PeriodicReportIntakeFunnel = {
    online_submissions: number;
    manual_submissions: number;
    converted_to_letters: number;
};

export type PeriodicReportSenderBreakdown = {
    name: string;
    total: number;
    percent: number;
};

export type PeriodicReportSourceBreakdown = {
    source: ReportLetterSource;
    label: string;
    total: number;
    percent: number;
};

export type PeriodicReportTrendPoint = {
    label: string;
    received: number;
    processing_started: number;
    completed: number;
};

export type PeriodicReportPerformance = {
    position_code: string;
    position_name: string;
    official_name: string | null;
    unit_name: string | null;
    level_code: 'ASSISTANT' | 'SECTION_HEAD';
    assigned: number;
    pending: number;
    in_progress: number;
    completed: number;
    completion_percent: number;
    average_completion_hours: number | null;
};

export type PeriodicReportBranchProgress = {
    total: number;
    pending: number;
    in_progress: number;
    completed: number;
    percent_complete: number;
};

export type PeriodicReportLetterItem = {
    reference: string;
    agenda_number: string;
    subject: string;
    sender_organization_name: string;
    source: ReportLetterSource;
    status: ReportLetterStatus;
    received_at: string;
    processing_started_at: string | null;
    completed_at: string | null;
    turnaround_hours: number | null;
    participant_position_codes: string[];
    branch_progress: PeriodicReportBranchProgress;
    links: {
        detail: string;
    };
};

export type ReportGraphAttention = {
    needs_attention: boolean;
    idle_hours: number | null;
    reason: string | null;
};

export type ReportAggregateGraphNode = {
    reference: string;
    recipient_position: ReportProcessPosition;
    progress: PeriodicReportBranchProgress;
    last_activity_at: string | null;
    attention: ReportGraphAttention;
};

export type ReportAggregateSectionHeadNode = ReportAggregateGraphNode;

export type ReportAggregateAssistantNode = ReportAggregateGraphNode & {
    children: ReportAggregateSectionHeadNode[];
};

export type ReportAggregateExecutiveNode = ReportAggregateGraphNode & {
    children: ReportAggregateAssistantNode[];
};

export type PeriodicReportOrganizationGraph = {
    generated_at: string;
    executives: ReportAggregateExecutiveNode[];
};

export type PaginatedPeriodicReportLetters = {
    data: PeriodicReportLetterItem[];
    pagination: RoutingPagination;
};

export type PeriodicReportRoutes = {
    index: string;
    export_summary?: string;
    export_letters?: string;
};

export type PeriodicReportPageProps = {
    summary?: PeriodicReportSummary;
    sourceBreakdown?: PeriodicReportSourceBreakdown[];
    intakeFunnel?: PeriodicReportIntakeFunnel;
    senderBreakdown?: PeriodicReportSenderBreakdown[];
    trend?: PeriodicReportTrendPoint[];
    assistantPerformance?: PeriodicReportPerformance[];
    sectionHeadPerformance?: PeriodicReportPerformance[];
    letters?: PaginatedPeriodicReportLetters;
    organizationGraph?: PeriodicReportOrganizationGraph;
    filters?: PeriodicReportFilters;
    scope?: PeriodicReportScope;
    routes?: PeriodicReportRoutes;
    canExport?: boolean;
    preview?: boolean;
};

export type ReportProcessPosition = {
    code: string;
    name: string;
    unit_name: string | null;
    official_name: string | null;
};

export type ReportProcessInstruction = {
    code: string;
    name: string;
};

export type ReportProcessFollowUp = {
    note: string;
    created_at: string;
    created_by: RoutingActor;
};

export type ReportProcessSectionBranch = {
    reference: string;
    recipient_position: ReportProcessPosition;
    status: 'PENDING' | 'IN_PROGRESS' | 'COMPLETED';
    received_at: string;
    started_at: string | null;
    completed_at: string | null;
    instructions: ReportProcessInstruction[];
    instruction_note: string | null;
    disposed_by: RoutingActor;
    disposed_at: string;
    follow_ups: ReportProcessFollowUp[];
    completion_note: string | null;
    completed_by: RoutingActor | null;
    attention: ReportGraphAttention;
};

export type ReportProcessAssistantBranch = {
    reference: string;
    recipient_position: ReportProcessPosition;
    status: 'PENDING' | 'COMPLETED';
    received_at: string;
    forwarded_at: string | null;
    instructions: ReportProcessInstruction[];
    instruction_note: string | null;
    disposed_by: RoutingActor;
    disposed_at: string;
    children: ReportProcessSectionBranch[];
    attention: ReportGraphAttention;
};

export type ReportProcessDetail = {
    letter: {
        reference: string;
        agenda_number: string;
        subject: string;
        sender_organization_name: string;
        external_letter_number: string | null;
        source: ReportLetterSource;
        status: ReportLetterStatus;
        received_at: string;
        completed_at: string | null;
    };
    initial_route: {
        target_position: ReportProcessPosition;
        routed_by: RoutingActor;
        routed_at: string;
    };
    branches: ReportProcessAssistantBranch[];
    progress: PeriodicReportBranchProgress;
    visibility_note: string;
};

export type ReportInspectorTiming = {
    label: string;
    value: string | null;
};

export type ReportNodeInspectorData = {
    reference: string;
    context: 'AGGREGATE' | 'LETTER';
    level: 'EXECUTIVE_ENTRY' | 'ASSISTANT' | 'SECTION_HEAD';
    position: ReportProcessPosition;
    status: 'PENDING' | 'IN_PROGRESS' | 'COMPLETED' | null;
    progress: PeriodicReportBranchProgress | null;
    attention: ReportGraphAttention | null;
    timings: ReportInspectorTiming[];
    instructions: ReportProcessInstruction[];
    instruction_note: string | null;
    decided_by: RoutingActor | null;
    decided_at: string | null;
    follow_ups: ReportProcessFollowUp[];
    completion_note: string | null;
    completed_by: RoutingActor | null;
    participant_position_codes: string[];
};

export type PeriodicReportDetailRoutes = PeriodicReportRoutes;

export type PeriodicReportDetailPageProps = {
    report?: ReportProcessDetail;
    summary?: PeriodicReportSummary;
    sourceBreakdown?: PeriodicReportSourceBreakdown[];
    intakeFunnel?: PeriodicReportIntakeFunnel;
    senderBreakdown?: PeriodicReportSenderBreakdown[];
    trend?: PeriodicReportTrendPoint[];
    letters?: PaginatedPeriodicReportLetters;
    filters?: PeriodicReportFilters;
    scope?: PeriodicReportScope;
    canExport?: boolean;
    routes?: PeriodicReportDetailRoutes;
    preview?: boolean;
};

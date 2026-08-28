export type PrivilegeAuditAction =
    'INTERNAL_ACCOUNT_PROVISIONED' | 'ROLE_CHANGED' | 'PERMISSION_CHANGED';

export type PrivilegeAuditSource = 'web' | 'console';

export type PrivilegeAuditTargetType = 'user' | 'role' | 'permission';

export type PrivilegeAuditValue = string | number | boolean | null | string[];

export type PrivilegeAuditChangeSet = Record<string, PrivilegeAuditValue>;

export type PrivilegeAuditActor = {
    kind: 'user' | 'system';
    id: number | null;
    name: string;
    email: string | null;
};

export type PrivilegeAuditTarget = {
    type: PrivilegeAuditTargetType;
    id: number | null;
    label: string;
    secondary: string | null;
    exists: boolean;
};

export type PrivilegeAuditRecord = {
    id: number;
    action: PrivilegeAuditAction;
    change: string | null;
    actor: PrivilegeAuditActor;
    target: PrivilegeAuditTarget;
    before: PrivilegeAuditChangeSet | null;
    after: PrivilegeAuditChangeSet | null;
    source: PrivilegeAuditSource;
    command: string | null;
    request_id: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
};

export type PrivilegeAuditFilters = {
    action: PrivilegeAuditAction | '';
    source: PrivilegeAuditSource | '';
    actor: string;
    target_type: PrivilegeAuditTargetType | '';
    target: string;
    date_from: string;
    date_to: string;
};

export type PrivilegeAuditFilterOption<T extends string> = {
    value: T;
    label: string;
};

export type PrivilegeAuditFilterOptions = {
    actions: PrivilegeAuditFilterOption<PrivilegeAuditAction>[];
    sources: PrivilegeAuditFilterOption<PrivilegeAuditSource>[];
    target_types: PrivilegeAuditFilterOption<PrivilegeAuditTargetType>[];
};

export type PaginatedPrivilegeAudits = {
    data: PrivilegeAuditRecord[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type PrivilegeAuditSummary = {
    total: number;
    web: number;
    console: number;
};

export type PrivilegeAuditRoutes = {
    index: string;
};

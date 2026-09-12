export type OnlineUserItem = {
    id: number;
    name: string;
    email: string;
    account_type: 'INTERNAL' | 'PUBLIC' | string;
    last_seen: string;
    ip_address?: string | null;
};

export type AdminUserMetrics = {
    online_count: number;
    recent_online: OnlineUserItem[];
    total: number;
    internal_count: number;
    public_count: number;
    active_count: number;
    suspended_count: number;
    pending_invitations_count: number;
};

export type AdminSecurityMetrics = {
    internal_mfa_enabled: number;
    internal_mfa_percentage: number;
    super_admins_count: number;
    super_admins_without_mfa: number;
    passkeys_count: number;
};

export type TopPendingUnitItem = {
    unit_name: string;
    unit_code: string;
    position_name: string;
    pending_count: number;
};

export type AdminWorkflowMetrics = {
    incoming_letters_count: number;
    active_dispositions_count: number;
    completed_dispositions_count: number;
    pending_submissions_count: number;
    top_pending_units: TopPendingUnitItem[];
};

export type AdminRecentEventItem = {
    id: number;
    event_type: string;
    user_name: string;
    user_email?: string | null;
    description: string;
    created_at_human: string;
};

export type AdminOrganizationMetrics = {
    units_count: number;
    positions_count: number;
    active_assignments_count: number;
};

export type AdminDashboardData = {
    users: AdminUserMetrics;
    security: AdminSecurityMetrics;
    workflow: AdminWorkflowMetrics;
    recent_events: AdminRecentEventItem[];
    organization: AdminOrganizationMetrics;
};

export type UserAccountType = 'INTERNAL' | 'PUBLIC';

export type UserInvitationStatus =
    'PENDING' | 'ACCEPTED' | 'REVOKED' | 'EXPIRED' | 'INVALID';

export type UserAccountEventType =
    | 'INVITATION_CREATED'
    | 'INVITATION_RESENT'
    | 'INVITATION_ACCEPTED'
    | 'INVITATION_REVOKED'
    | 'USER_DEACTIVATED'
    | 'USER_REACTIVATED'
    | 'SESSIONS_REVOKED'
    | 'PASSWORD_RESET_LINK_SENT'
    | 'MFA_RESET'
    | 'ROLES_DETACHED';

export interface UserPositionAssignmentInfo {
    id: number;
    position_id: number;
    position_name: string;
    unit_name: string;
    unit_code: string;
    level_code: string;
    started_at: string;
    ended_at?: string | null;
}

export interface UserSubmissionMetrics {
    total: number;
    draft: number;
    submitted: number;
    verified: number;
    rejected: number;
}

export interface UserManagementItem {
    id: number;
    name: string;
    email: string;
    account_type: UserAccountType;
    is_active: boolean;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    created_at: string;
    updated_at: string;
    roles: string[];
    active_position: UserPositionAssignmentInfo | null;
    historical_positions: UserPositionAssignmentInfo[];
    submission_metrics: UserSubmissionMetrics;
    phone_number?: string | null;
    active_sessions_count: number;
    last_login_at?: string | null;
    last_login_ip?: string | null;
    last_login_device?: string | null;
    can_deactivate: boolean;
    deactivation_block_reason: string | null;
}

export interface UserInvitationItem {
    id: number;
    public_id: string;
    name: string;
    email: string;
    phone_number?: string | null;
    account_type: UserAccountType;
    status: UserInvitationStatus;
    expires_at: string;
    accepted_at: string | null;
    revoked_at: string | null;
    created_at: string;
    invited_by: {
        id: number;
        name: string;
        email: string;
    } | null;
}

export interface UserAccountEventItem {
    id: number;
    user_id: number | null;
    user_name: string | null;
    user_email: string | null;
    event_type: UserAccountEventType;
    actor: {
        id: number;
        name: string;
        email: string;
    } | null;
    description: string;
    reason: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
}

export interface UserManagementFilter {
    search: string;
    type: 'ALL' | 'PUBLIC' | 'INTERNAL';
    status: 'ALL' | 'ACTIVE' | 'DEACTIVATED';
    verified: 'ALL' | 'VERIFIED' | 'UNVERIFIED';
    role: string;
    unit: string;
}

export interface UserManagementCapabilities {
    can_view_users: boolean;
    can_invite_users: boolean;
    can_manage_user_status: boolean;
    can_manage_user_security: boolean;
}

export interface InviteUserPayload {
    name: string;
    email: string;
    phone_number?: string | null;
    account_type: UserAccountType;
}

export interface DeactivateUserPayload {
    reason: string;
}

export interface ResetUserMfaPayload {
    super_admin_password: string;
    super_admin_two_factor_code?: string;
}

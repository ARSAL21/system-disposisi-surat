export type User = {
    id: number;
    name: string;
    email: string;
    account_type: 'PUBLIC' | 'INTERNAL';
    is_active: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AuthCapabilities = {
    can_view_authorization: boolean;
    can_manage_authorization: boolean;
    can_view_organization: boolean;
    can_manage_organization: boolean;
    can_manage_position_assignments: boolean;
    can_view_privilege_audits: boolean;
    can_view_intake: boolean;
    can_screen_intake: boolean;
    can_decide_intake: boolean;
};

export type Auth = {
    user: User;
    capabilities: AuthCapabilities;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

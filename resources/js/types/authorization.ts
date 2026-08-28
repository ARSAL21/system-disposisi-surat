export type AuthorizationPermission = {
    name: string;
    label: string;
    description: string;
    group: string;
};

export type AuthorizationRole = {
    id: number;
    name: string;
    guard_name: string;
    is_protected: boolean;
    is_assigned_to_actor: boolean;
    user_count: number;
    permissions: string[];
    capabilities: {
        rename: boolean;
        delete: boolean;
        synchronize_permissions: boolean;
    };
    links: {
        update: string;
        delete: string;
        permissions: string;
    };
};

export type AuthorizationUserRole = {
    id: number;
    name: string;
    is_protected: boolean;
};

export type AuthorizationUser = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    is_verified: boolean;
    roles: AuthorizationUserRole[];
    capabilities: {
        synchronize_roles: boolean;
    };
    links: {
        roles: string;
    };
};

export type PaginatedAuthorizationUsers = {
    data: AuthorizationUser[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type AuthorizationSummary = {
    roles: number;
    custom_roles: number;
    permissions: number;
    internal_users: number;
};

export type AuthorizationMutationSecurity = {
    can_manage: boolean;
    mfa_enabled: boolean;
    password_confirmed: boolean;
    password_confirmed_until: string | null;
    can_mutate: boolean;
    activation_url: string;
    security_settings_url: string;
};

export type AuthorizationFilters = {
    tab: 'roles' | 'users';
    user_search: string;
};

export type AuthorizationRoutes = {
    index: string;
    store: string;
};

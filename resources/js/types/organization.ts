export type OrganizationOption = {
    id: number;
    name: string;
};

export type OrganizationalUnitOption = OrganizationOption & {
    parent_id?: number | null;
    code?: string | null;
};

export type PositionLevel = {
    id: number;
    code: string;
    name: string;
    hierarchy_order: number;
    is_active: boolean;
    is_protected: boolean;
    position_count: number;
};

export type OrganizationalUnit = {
    id: number;
    parent_id: number | null;
    code: string | null;
    name: string;
    is_active: boolean;
    parent: OrganizationOption | null;
    children_count: number;
    positions_count: number;
    capabilities: { update: boolean; change_status: boolean };
    links: { update: string; status: string };
};

export type AssignmentUser = {
    id: number;
    name: string;
    email: string;
};

export type PositionAssignment = {
    id: number;
    started_at: string;
    ended_at: string | null;
    is_active: boolean;
    user: AssignmentUser;
    assigned_by: { id: number; name: string } | null;
    links: { end: string };
};

export type ActivePositionAssignment = Pick<
    PositionAssignment,
    'id' | 'started_at' | 'user' | 'links'
>;

export type OrganizationPosition = {
    id: number;
    position_level_id: number;
    organizational_unit_id: number | null;
    code: string;
    name: string;
    is_active: boolean;
    level: PositionLevel;
    unit: OrganizationOption | null;
    active_assignment: ActivePositionAssignment | null;
    assignment_count: number;
    capabilities: { update: boolean; change_status: boolean };
    links: {
        update: string;
        status: string;
        assign: string;
        replace: string;
    };
};

export type Paginated<T> = {
    data: T[];
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

export type MutationSecurityState = {
    can_manage: boolean;
    mfa_enabled: boolean;
    password_confirmed: boolean;
    password_confirmed_until: string | null;
    can_mutate: boolean;
    activation_url: string;
    security_settings_url: string;
};

export type OrganizationStructureFilters = {
    section: 'levels' | 'units' | 'positions';
    search: string;
    status: 'all' | 'active' | 'inactive';
    position_level_id: number | null;
    organizational_unit_id: number | null;
};

export type PositionAssignmentFilters = {
    search: string;
    status: 'all' | 'occupied' | 'vacant' | 'inactive';
    position_level_id: number | null;
    organizational_unit_id: number | null;
    selected_position: number | null;
};

export type OrganizationStructurePageProps = {
    levels: PositionLevel[];
    units: Paginated<OrganizationalUnit> | null;
    positions: Paginated<OrganizationPosition> | null;
    unitOptions: OrganizationalUnitOption[];
    summary: {
        levels: number;
        active_units: number;
        active_positions: number;
        occupied_positions: number;
    };
    filters: OrganizationStructureFilters;
    mutationSecurity: MutationSecurityState;
    routes: {
        index: string;
        store_unit: string;
        store_position: string;
        assignments: string;
    };
};

export type PositionAssignmentPageProps = {
    positions: Paginated<OrganizationPosition>;
    selectedPosition: OrganizationPosition | null;
    history: Paginated<PositionAssignment> | null;
    levels: PositionLevel[];
    units: OrganizationOption[];
    users: AssignmentUser[];
    summary: {
        positions: number;
        occupied: number;
        vacant: number;
        inactive: number;
    };
    filters: PositionAssignmentFilters;
    mutationSecurity: MutationSecurityState;
    routes: { index: string; structure: string };
};

export type User = {
    id: number;
    name: string;
    email: string;
    account_type: 'PUBLIC' | 'INTERNAL';
    is_active: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    password_confirmed?: boolean;
    confirm_password_url?: string;
    requires_mfa_setup?: boolean;
    is_super_admin?: boolean;
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
    can_view_letter_activities: boolean;
    can_view_document_versions: boolean;
    can_view_letter_routing: boolean;
    can_create_letter_routing: boolean;
    can_view_executive_inbox: boolean;
    can_view_dispositions: boolean;
    can_create_dispositions: boolean;
    can_process_dispositions: boolean;
    can_view_disposition_instructions: boolean;
    can_manage_disposition_instructions: boolean;
    can_view_reports: boolean;
    can_export_reports: boolean;
    can_view_intake: boolean;
    can_screen_intake: boolean;
    can_decide_intake: boolean;
    can_create_manual_intake: boolean;
    can_view_incoming_register: boolean;
    can_view_letter_responses: boolean;
    can_contribute_letter_responses: boolean;
    can_review_letter_responses: boolean;
    can_authorize_letter_responses: boolean;
    can_view_outgoing_register?: boolean;
    can_number_outgoing_letters?: boolean;
    can_verify_outgoing_letters?: boolean;
    can_deliver_outgoing_letters?: boolean;
    can_view_users?: boolean;
    can_invite_users?: boolean;
    can_manage_user_status?: boolean;
    can_manage_user_security?: boolean;
    can_view_outgoing_templates?: boolean;
    can_manage_outgoing_templates?: boolean;
    can_view_standalone_outgoing?: boolean;
    can_create_standalone_outgoing?: boolean;
    can_review_standalone_outgoing?: boolean;
    can_approve_standalone_outgoing?: boolean;
    can_view_expert_consultations?: boolean;
    can_request_expert_consultations?: boolean;
    can_respond_expert_consultations?: boolean;
    can_coordinate_expert_consultations?: boolean;
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

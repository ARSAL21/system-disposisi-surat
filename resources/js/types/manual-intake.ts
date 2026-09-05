import type { ScreeningChecklistItem } from './intake';

export type ManualIntakeStep = 1 | 2 | 3;

export type ManualIntakeFormPayload = {
    sender_organization_name: string;
    contact_name: string;
    contact_email: string;
    contact_phone: string;
    received_at: string;
    external_letter_number: string;
    external_letter_date: string;
    subject: string;
    summary: string;
    document: File | null;
    checklist: ScreeningChecklistItem[];
    screening_note: string;
};

export type ManualIntakeRoutes = {
    store: string;
    intake_index: string;
    incoming_register: string;
};

export type ManualIntakeInitialData = Omit<
    ManualIntakeFormPayload,
    'document'
> & {
    existing_document: {
        original_filename: string;
        size_bytes: number;
    } | null;
    return_note: string | null;
};

export type ManualIntakePageProps = {
    routes?: ManualIntakeRoutes;
    mode?: 'create' | 'revision';
    initial?: ManualIntakeInitialData;
    preview?: boolean;
};

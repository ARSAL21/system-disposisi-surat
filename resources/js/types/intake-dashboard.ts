import type { SubmissionStatus } from './submission';

export type IntakeQueueMetrics = {
    submitted_count: number;
    internal_revision_count: number;
    ready_for_approval_count: number;
    registered_count: number;
};

export type IntakeQueueItem = {
    public_id: string;
    source: 'ONLINE' | 'MANUAL' | string;
    status: SubmissionStatus | string;
    sender_organization_name: string;
    contact_name: string;
    contact_email?: string | null;
    contact_phone?: string | null;
    external_letter_number?: string | null;
    external_letter_date?: string | null;
    subject: string;
    summary?: string | null;
    submitted_at: string;
    document?: {
        original_filename: string;
        mime_type: string;
        size_bytes: number;
    } | null;
    internal_revision_note?: string | null;
    links: {
        show: string;
        document_preview?: string | null;
        document_download?: string | null;
    };
};

export type IntakeDashboardData = {
    metrics: IntakeQueueMetrics;
    recent_submissions: IntakeQueueItem[];
};

import type {
    IntakeDashboardData,
    IntakeQueueItem,
    IntakeQueueMetrics,
} from '@/types';

export const previewQueueMetrics: IntakeQueueMetrics = {
    submitted_count: 6,
    internal_revision_count: 2,
    ready_for_approval_count: 4,
    registered_count: 28,
};

export const previewQueueSubmissions: IntakeQueueItem[] = [
    {
        public_id: '01K3QW4N8X6M2H7R9T5V0C3B1A',
        source: 'ONLINE',
        status: 'INTERNAL_REVISION_REQUIRED',
        sender_organization_name: 'Forum Kerukunan Warga Kota',
        contact_name: 'Rahmawati Yusuf',
        contact_email: 'rahmawati@example.test',
        contact_phone: '0812-3456-7890',
        external_letter_number: '014/FKWK/VIII/2026',
        external_letter_date: '2026-08-26',
        subject:
            'Permohonan audiensi mengenai layanan administrasi kependudukan',
        summary:
            'Permohonan jadwal audiensi perwakilan warga untuk menyampaikan aspirasi peningkatan sarana dan kecepatan pelayanan administrasi kependudukan.',
        submitted_at: '2026-08-30T02:15:00.000Z',
        document: {
            original_filename: 'permohonan-audiensi-fkwk.pdf',
            mime_type: 'application/pdf',
            size_bytes: 2483200,
        },
        internal_revision_note:
            'Mohon periksa kembali kelengkapan lampiran daftar perwakilan warga dan pastikan stempel instansi pemohon tertera jelas pada berkas PDF.',
        links: {
            show: '/back-office/intake/submissions/01K3QW4N8X6M2H7R9T5V0C3B1A',
            document_preview:
                '/back-office/intake/submissions/01K3QW4N8X6M2H7R9T5V0C3B1A/document',
            document_download:
                '/back-office/intake/submissions/01K3QW4N8X6M2H7R9T5V0C3B1A/document/download',
        },
    },
    {
        public_id: '01K3QW5M9Y7N3J8S0U6W1D4C2B',
        source: 'ONLINE',
        status: 'SUBMITTED',
        sender_organization_name: 'PT Sumber Makmur Sejahtera',
        contact_name: 'Ir. Hendra Gunawan',
        contact_email: 'hendra.g@makmur.test',
        contact_phone: '0813-8899-7766',
        external_letter_number: '089/SMS-DIR/VIII/2026',
        external_letter_date: '2026-08-29',
        subject: 'Permohonan izin pemanfaatan area publik untuk pameran UMKM',
        summary:
            'Pengajuan izin penggunaan pelataran alun-alun kota untuk pelaksanaan pameran produk UMKM binaan swasta.',
        submitted_at: '2026-08-30T08:30:00.000Z',
        document: {
            original_filename: 'proposal_pameran_umkm_sms.pdf',
            mime_type: 'application/pdf',
            size_bytes: 4194304,
        },
        internal_revision_note: null,
        links: {
            show: '/back-office/intake/submissions/01K3QW5M9Y7N3J8S0U6W1D4C2B',
            document_preview:
                '/back-office/intake/submissions/01K3QW5M9Y7N3J8S0U6W1D4C2B/document',
            document_download:
                '/back-office/intake/submissions/01K3QW5M9Y7N3J8S0U6W1D4C2B/document/download',
        },
    },
    {
        public_id: '01K3QW6P0Z8O4K9T1V7X2E5D3C',
        source: 'MANUAL',
        status: 'SUBMITTED',
        sender_organization_name: 'Yayasan Pendidikan Bangsa Mandiri',
        contact_name: 'Siti Aminah, M.Pd.',
        contact_email: 'siti.aminah@ypbm.test',
        contact_phone: '0852-1122-3344',
        external_letter_number: '102/YPBM/VIII/2026',
        external_letter_date: '2026-08-28',
        subject: 'Undangan kehormatan pembukaan Olimpiade Sains Pelajar Daerah',
        summary:
            'Undangan kepada Wali Kota dan jajaran pimpinan daerah untuk menghadiri serta membuka secara resmi Olimpiade Sains tingkat Kota.',
        submitted_at: '2026-08-30T09:45:00.000Z',
        document: {
            original_filename: 'undangan_pembukaan_olimpiade.pdf',
            mime_type: 'application/pdf',
            size_bytes: 1850000,
        },
        internal_revision_note: null,
        links: {
            show: '/back-office/intake/submissions/01K3QW6P0Z8O4K9T1V7X2E5D3C',
            document_preview:
                '/back-office/intake/submissions/01K3QW6P0Z8O4K9T1V7X2E5D3C/document',
            document_download:
                '/back-office/intake/submissions/01K3QW6P0Z8O4K9T1V7X2E5D3C/document/download',
        },
    },
];

export const previewIntakeDashboardData: IntakeDashboardData = {
    metrics: previewQueueMetrics,
    recent_submissions: previewQueueSubmissions,
};

export function getSubmissionStatusMeta(status: string): {
    label: string;
    badgeClass: string;
    dotClass: string;
} {
    switch (status) {
        case 'SUBMITTED':
            return {
                label: 'Perlu Screening',
                badgeClass:
                    'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
                dotClass: 'bg-blue-500 animate-pulse',
            };
        case 'INTERNAL_REVISION_REQUIRED':
            return {
                label: 'Perbaikan dari Kabag',
                badgeClass:
                    'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950/60 dark:text-rose-300',
                dotClass: 'bg-rose-500',
            };
        case 'REVISION_REQUIRED':
            return {
                label: 'Revisi Pemohon',
                badgeClass:
                    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                dotClass: 'bg-amber-500',
            };
        case 'READY_FOR_APPROVAL':
            return {
                label: 'Di Meja Kabag',
                badgeClass:
                    'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300',
                dotClass: 'bg-indigo-500',
            };
        case 'REGISTERED':
            return {
                label: 'Selesai Diregistrasi',
                badgeClass:
                    'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                dotClass: 'bg-emerald-500',
            };
        case 'REJECTED':
            return {
                label: 'Ditolak',
                badgeClass:
                    'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                dotClass: 'bg-slate-500',
            };
        default:
            return {
                label: status,
                badgeClass: 'border-border bg-muted text-muted-foreground',
                dotClass: 'bg-muted-foreground',
            };
    }
}

export function formatSubmissionDate(isoDate: string): string {
    if (!isoDate) {
        return '-';
    }

    try {
        const date = new Date(isoDate);

        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    } catch {
        return isoDate;
    }
}

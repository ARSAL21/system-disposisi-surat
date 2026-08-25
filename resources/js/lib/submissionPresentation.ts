import type { SubmissionStatus } from '@/types';

export type SubmissionStatusPresentation = {
    label: string;
    description: string;
    badgeClass: string;
    dotClass: string;
};

const statusPresentation: Record<
    SubmissionStatus,
    SubmissionStatusPresentation
> = {
    DRAFT: {
        label: 'Draft',
        description: 'Belum dikirim dan masih dapat diperbarui.',
        badgeClass:
            'border-brand-amber/45 bg-brand-amber-soft text-brand-amber-foreground',
        dotClass: 'bg-brand-amber-strong',
    },
    SUBMITTED: {
        label: 'Terkirim',
        description: 'Menunggu pemeriksaan administratif Bagian Umum.',
        badgeClass:
            'border-brand-teal/35 bg-brand-teal-soft text-brand-teal-foreground',
        dotClass: 'bg-brand-teal',
    },
    REGISTERED: {
        label: 'Terdaftar',
        description: 'Telah diregistrasikan sebagai surat masuk resmi.',
        badgeClass:
            'border-emerald-500/35 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300',
        dotClass: 'bg-emerald-500',
    },
    REJECTED: {
        label: 'Ditolak',
        description: 'Tidak dapat dilanjutkan pada pemeriksaan administratif.',
        badgeClass:
            'border-brand-orange/35 bg-brand-orange-soft text-brand-orange-foreground',
        dotClass: 'bg-brand-orange',
    },
};

export function getSubmissionStatusPresentation(
    status: SubmissionStatus,
): SubmissionStatusPresentation {
    return statusPresentation[status];
}

export function formatSubmissionDate(value: string | null): string {
    if (!value) {
        return 'Belum tersedia';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatSubmissionDateTime(value: string | null): string {
    if (!value) {
        return 'Belum tersedia';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

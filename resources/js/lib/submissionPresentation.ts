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
            'border-warning-foreground/20 bg-warning text-warning-foreground',
        dotClass: 'bg-warning-foreground',
    },
    SUBMITTED: {
        label: 'Terkirim',
        description: 'Menunggu pemeriksaan administratif Bagian Umum.',
        badgeClass: 'border-info-foreground/20 bg-info text-info-foreground',
        dotClass: 'bg-info-foreground',
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
        badgeClass: 'border-destructive/25 bg-destructive/10 text-destructive',
        dotClass: 'bg-destructive',
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

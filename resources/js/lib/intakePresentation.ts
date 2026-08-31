import type { IntakeSubmissionStatus } from '@/types';

type IntakeStatusPresentation = {
    label: string;
    description: string;
    badgeClass: string;
};

const statusPresentation: Record<
    IntakeSubmissionStatus,
    IntakeStatusPresentation
> = {
    SUBMITTED: {
        label: 'Menunggu pemeriksaan awal',
        description: 'Belum diperiksa oleh petugas administrasi.',
        badgeClass:
            'border-blue-500/25 bg-blue-500/10 text-blue-800 dark:text-blue-200',
    },
    REVISION_REQUIRED: {
        label: 'Perlu perbaikan pengirim',
        description: 'Dikembalikan kepada pengirim untuk diperbaiki.',
        badgeClass:
            'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200',
    },
    READY_FOR_APPROVAL: {
        label: 'Menunggu Kabag Umum',
        description: 'Lolos pemeriksaan awal dan siap mendapat keputusan.',
        badgeClass:
            'border-violet-500/25 bg-violet-500/10 text-violet-800 dark:text-violet-200',
    },
    INTERNAL_REVISION_REQUIRED: {
        label: 'Dikembalikan ke petugas',
        description:
            'Rancangan pencatatan resmi perlu diperbaiki oleh petugas.',
        badgeClass:
            'border-orange-500/25 bg-orange-500/10 text-orange-800 dark:text-orange-200',
    },
    REGISTERED: {
        label: 'Terdaftar resmi',
        description: 'Telah disahkan menjadi surat masuk resmi.',
        badgeClass:
            'border-emerald-500/25 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200',
    },
    REJECTED: {
        label: 'Ditolak administratif',
        description: 'Dihentikan melalui keputusan Kepala Bagian Umum.',
        badgeClass: 'border-destructive/25 bg-destructive/10 text-destructive',
    },
};

export function getIntakeStatusPresentation(
    status: IntakeSubmissionStatus,
): IntakeStatusPresentation {
    return statusPresentation[status];
}

export function shortSubmissionId(publicId: string): string {
    return `${publicId.slice(0, 8)}...${publicId.slice(-4)}`;
}

export const intakeRoutes = {
    index: '/back-office/intake/submissions',
    show: (publicId: string) =>
        `/back-office/intake/submissions/${encodeURIComponent(publicId)}`,
};

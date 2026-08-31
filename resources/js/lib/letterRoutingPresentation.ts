import type {
    DispositionRecipientStatus,
    InitialRouteStatus,
    LetterRoutingStatus,
} from '@/types';

export const letterRoutingStatusLabels: Record<LetterRoutingStatus, string> = {
    REGISTERED: 'Menunggu routing',
    ROUTED: 'Sudah diarahkan',
};

export const initialRouteStatusLabels: Record<InitialRouteStatus, string> = {
    PENDING: 'Menunggu disposisi pimpinan',
    COMPLETED: 'Disposisi pertama dibuat',
};

export const dispositionRecipientStatusLabels: Record<
    DispositionRecipientStatus,
    string
> = {
    PENDING: 'Menunggu tindak lanjut',
    IN_PROGRESS: 'Sedang ditangani',
    COMPLETED: 'Selesai',
};

export function letterRoutingStatusClass(status: LetterRoutingStatus): string {
    return status === 'REGISTERED'
        ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300'
        : 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/70 dark:bg-violet-950/40 dark:text-violet-300';
}

export function initialRouteStatusClass(status: InitialRouteStatus): string {
    return status === 'PENDING'
        ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/40 dark:text-blue-300'
        : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300';
}

export function dispositionRecipientStatusClass(
    status: DispositionRecipientStatus,
): string {
    if (status === 'PENDING') {
        return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300';
    }

    if (status === 'IN_PROGRESS') {
        return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/40 dark:text-blue-300';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300';
}

export function formatRoutingDateTime(
    value: string | null | undefined,
): string {
    if (!value) {
        return '-';
    }

    try {
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Makassar',
            timeZoneName: 'short',
        }).format(new Date(value));
    } catch {
        return value;
    }
}

export function formatRoutingFileSize(sizeBytes: number): string {
    if (!Number.isFinite(sizeBytes) || sizeBytes < 0) {
        return '-';
    }

    if (sizeBytes < 1024) {
        return `${sizeBytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let size = sizeBytes / 1024;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex += 1;
    }

    return `${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 1,
    }).format(size)} ${units[unitIndex]}`;
}

import type { DocumentArchiveStatus } from '@/types';

export const documentArchiveStatusLabels: Record<
    DocumentArchiveStatus,
    string
> = {
    REGISTERED: 'Teregistrasi',
    ROUTED: 'Diteruskan',
    IN_PROGRESS: 'Dalam disposisi',
    COMPLETED: 'Selesai',
};

export function documentArchiveStatusClass(
    status: DocumentArchiveStatus,
): string {
    switch (status) {
        case 'REGISTERED':
            return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/45 dark:text-blue-300';
        case 'ROUTED':
            return 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/70 dark:bg-violet-950/45 dark:text-violet-300';
        case 'IN_PROGRESS':
            return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/45 dark:text-amber-300';
        case 'COMPLETED':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/45 dark:text-emerald-300';
    }
}

export function formatArchiveDateTime(value: string): string {
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
        }).format(new Date(value));
    } catch {
        return value;
    }
}

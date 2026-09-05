import type { IncomingRegisterSource, IncomingRegisterStatus } from '@/types';

export const incomingRegisterStatusLabels: Record<
    IncomingRegisterStatus,
    string
> = {
    REGISTERED: 'Teregistrasi',
    ROUTED: 'Sudah diarahkan',
    IN_PROGRESS: 'Dalam disposisi',
    COMPLETED: 'Selesai',
};

export const incomingRegisterSourceLabels: Record<
    IncomingRegisterSource,
    string
> = {
    ONLINE: 'Online',
    MANUAL: 'Surat fisik',
};

export function incomingRegisterStatusClass(
    status: IncomingRegisterStatus,
): string {
    return {
        REGISTERED:
            'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/35 dark:text-blue-200',
        ROUTED: 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-900 dark:bg-violet-950/35 dark:text-violet-200',
        IN_PROGRESS:
            'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/35 dark:text-amber-200',
        COMPLETED:
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/35 dark:text-emerald-200',
    }[status];
}

export function incomingRegisterSourceClass(
    source: IncomingRegisterSource,
): string {
    return source === 'MANUAL'
        ? 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/35 dark:text-amber-200'
        : 'border-cyan-200 bg-cyan-50 text-cyan-900 dark:border-cyan-900 dark:bg-cyan-950/35 dark:text-cyan-200';
}

export function formatIncomingRegisterDateTime(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Makassar',
    }).format(date);
}

export function formatIncomingRegisterDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    const date = new Date(`${value}T00:00:00+08:00`);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeZone: 'Asia/Makassar',
    }).format(date);
}

export function formatIncomingRegisterBytes(bytes: number): string {
    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toLocaleString('id-ID', {
        maximumFractionDigits: 1,
    })} MB`;
}

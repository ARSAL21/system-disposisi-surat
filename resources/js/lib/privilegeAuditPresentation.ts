import type {
    PrivilegeAuditAction,
    PrivilegeAuditSource,
    PrivilegeAuditTargetType,
    PrivilegeAuditValue,
} from '@/types';

export const privilegeAuditActionLabels: Record<PrivilegeAuditAction, string> =
    {
        INTERNAL_ACCOUNT_PROVISIONED: 'Akun internal dibuat',
        ROLE_CHANGED: 'Role berubah',
        PERMISSION_CHANGED: 'Permission berubah',
    };

export const privilegeAuditTargetLabels: Record<
    PrivilegeAuditTargetType,
    string
> = {
    user: 'Akun internal',
    role: 'Role',
    permission: 'Permission',
};

export function privilegeAuditActionClass(
    action: PrivilegeAuditAction,
): string {
    return {
        INTERNAL_ACCOUNT_PROVISIONED:
            'border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-900 dark:bg-cyan-950/40 dark:text-cyan-200',
        ROLE_CHANGED:
            'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-200',
        PERMISSION_CHANGED:
            'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200',
    }[action];
}

export function privilegeAuditSourceLabel(
    source: PrivilegeAuditSource,
): string {
    return source === 'console' ? 'Console' : 'Web';
}

export function privilegeAuditSourceClass(
    source: PrivilegeAuditSource,
): string {
    return source === 'console'
        ? 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200'
        : 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200';
}

export function formatPrivilegeAuditDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Makassar',
    }).format(new Date(value));
}

export function formatPrivilegeAuditField(field: string): string {
    const labels: Record<string, string> = {
        account_type: 'Tipe akun',
        email: 'Email',
        email_verified_at: 'Email terverifikasi',
        guard_name: 'Guard',
        is_active: 'Status aktif',
        name: 'Nama',
        permissions: 'Permission',
        roles: 'Role',
    };

    return labels[field] ?? field.replaceAll('_', ' ');
}

export function formatPrivilegeAuditValue(value: PrivilegeAuditValue): string {
    if (value === null) {
        return 'Tidak tersedia';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    if (Array.isArray(value)) {
        return value.length ? value.join(', ') : 'Tidak ada';
    }

    return String(value);
}

import type {
    DocumentVersionHistoryResponse,
    DocumentVersionItem,
    DocumentVersionLetter,
} from '@/types';

export const previewLetter: DocumentVersionLetter = {
    id: 1,
    agenda_number: '001/UMUM/2026',
    agenda_year: 2026,
    subject: 'Permohonan audiensi dan penyampaian aspirasi layanan publik',
    status: 'REGISTERED',
    received_at: '2026-08-28T03:30:00.000Z',
};

export const previewVersions: DocumentVersionItem[] = [
    {
        id: 2,
        version_number: 2,
        is_current: true,
        replaces_version_number: 1,
        source: 'MANUAL_CORRECTION',
        original_filename: 'surat_permohonan_revisi_ttd_lengkap.pdf',
        mime_type: 'application/pdf',
        size_bytes: 2845610,
        sha256: 'a1b2c3d4e5f67890123456789abcdef0123456789abcdef0123456789abcdef0',
        correction_reason:
            'Penggantian dokumen resmi dikarenakan pada berkas awal (v1) lembar pengesahan belum memuat tanda tangan basah dan cap stempel resmi pimpinan lembaga.',
        uploaded_by: {
            id: 2,
            name: 'Drs. H. Ahmad Fauzi, M.Si.',
            position: 'Kepala Bagian Umum',
            unit: 'Bagian Umum',
        },
        recorded_by: {
            id: 2,
            name: 'Drs. H. Ahmad Fauzi, M.Si.',
            position: 'Kepala Bagian Umum',
            unit: 'Bagian Umum',
        },
        created_at: '2026-08-29T10:15:00.000Z',
        preview_url: '/back-office/letters/1/documents/2/preview',
        download_url: '/back-office/letters/1/documents/2/download',
    },
    {
        id: 1,
        version_number: 1,
        is_current: false,
        replaces_version_number: null,
        source: 'ONLINE_SUBMISSION',
        original_filename: 'surat_permohonan_awal_audiensi.pdf',
        mime_type: 'application/pdf',
        size_bytes: 2483200,
        sha256: 'f0e1d2c3b4a596877869504132231425364758697a8b9c0d1e2f3a4b5c6d7e8f',
        correction_reason: null,
        uploaded_by: {
            id: 10,
            name: 'Rahmawati Yusuf',
            position: 'Pemohon Publik',
            unit: 'Forum Kerukunan Warga Kota',
        },
        recorded_by: {
            id: 3,
            name: 'Budi Santoso, S.Kom.',
            position: 'Staf Administrasi Persuratan',
            unit: 'Bagian Umum',
        },
        created_at: '2026-08-28T01:31:00.000Z',
        preview_url: '/back-office/letters/1/documents/1/preview',
        download_url: '/back-office/letters/1/documents/1/download',
    },
];

export const previewDocumentVersionHistory: DocumentVersionHistoryResponse = {
    letter: previewLetter,
    versions: previewVersions,
    capabilities: {
        can_create_version: true,
    },
    next_version_number: 3,
    routes: {
        archive: '/back-office/previews/documents',
        store: '#preview-store',
    },
};

export function formatBytes(bytes: number, decimals = 2): string {
    if (bytes === 0) {
        return '0 Bytes';
    }

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

export function formatShortHash(hash: string): string {
    if (!hash || hash.length < 16) {
        return hash;
    }

    return `${hash.substring(0, 8)}...${hash.substring(hash.length - 8)}`;
}

export function formatDateTime(isoString: string): string {
    if (!isoString) {
        return '-';
    }

    try {
        const date = new Date(isoString);

        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZoneName: 'short',
        }).format(date);
    } catch {
        return isoString;
    }
}

export function getSourceBadge(source: string): {
    label: string;
    class: string;
} {
    switch (source) {
        case 'ONLINE_SUBMISSION':
            return {
                label: 'Pengajuan Publik (Online)',
                class: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
            };
        case 'MANUAL_CORRECTION':
            return {
                label: 'Koreksi Resmi (Kabag Umum)',
                class: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800',
            };
        case 'MANUAL_INTAKE':
            return {
                label: 'Penerimaan Langsung (Loket)',
                class: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
            };
        default:
            return {
                label: source,
                class: 'bg-muted text-muted-foreground border-border',
            };
    }
}

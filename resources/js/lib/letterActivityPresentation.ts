import type {
    LetterActivityAction,
    LetterActivityRecord,
    LetterActivityValue,
} from '@/types';

export const letterActivityActionLabels: Record<LetterActivityAction, string> = {
    SUBMISSION_SUBMITTED: 'Surat diajukan',
    SUBMISSION_RESUBMITTED: 'Surat diajukan kembali',
    SUBMISSION_REVISION_REQUESTED: 'Perbaikan diminta',
    SUBMISSION_READY_FOR_APPROVAL: 'Siap ditinjau Kabag',
    SUBMISSION_RETURNED_TO_STAFF: 'Dikembalikan ke petugas',
    SUBMISSION_REJECTED: 'Surat ditolak',
    LETTER_REGISTERED: 'Surat diregistrasi',
    DOCUMENT_VERSION_CREATED: 'Dokumen resmi dikunci',
};

export const letterActivityActionDescriptions: Record<
    LetterActivityAction,
    string
> = {
    SUBMISSION_SUBMITTED: 'Pengajuan baru masuk dari portal publik.',
    SUBMISSION_RESUBMITTED: 'Pemohon mengirim kembali surat setelah perbaikan.',
    SUBMISSION_REVISION_REQUESTED: 'Petugas meminta pemohon melengkapi pengajuan.',
    SUBMISSION_READY_FOR_APPROVAL:
        'Hasil pemeriksaan petugas diteruskan kepada Kepala Bagian Umum.',
    SUBMISSION_RETURNED_TO_STAFF:
        'Kepala Bagian Umum mengembalikan surat untuk diperiksa ulang.',
    SUBMISSION_REJECTED: 'Kepala Bagian Umum menolak pengajuan surat.',
    LETTER_REGISTERED: 'Surat memperoleh identitas dan nomor agenda resmi.',
    DOCUMENT_VERSION_CREATED:
        'Versi dokumen resmi dan sidik jari SHA-256 dicatat.',
};

export function letterActivityActionClass(action: LetterActivityAction): string {
    if (action === 'LETTER_REGISTERED') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300';
    }

    if (action === 'DOCUMENT_VERSION_CREATED') {
        return 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950/50 dark:text-violet-300';
    }

    if (
        action === 'SUBMISSION_REVISION_REQUESTED' ||
        action === 'SUBMISSION_RETURNED_TO_STAFF'
    ) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-300';
    }

    if (action === 'SUBMISSION_REJECTED') {
        return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300';
    }

    return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-300';
}

export function formatLetterActivityDateTime(
    value: string,
    timezone: string,
): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: timezone,
    }).format(new Date(value));
}

export function formatLetterActivityTime(
    value: string,
    timezone: string,
): string {
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone: timezone,
    }).format(new Date(value));
}

export function formatLetterActivityValue(value: LetterActivityValue): string {
    if (value === null) {
        return 'Tidak tersedia';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    return String(value);
}

export function letterActivitySearchText(activity: LetterActivityRecord): string {
    return [
        activity.target?.public_id,
        activity.target?.agenda_number,
        activity.target?.subject,
        activity.target?.sender,
    ]
        .filter(Boolean)
        .join(' ')
        .toLocaleLowerCase('id-ID');
}

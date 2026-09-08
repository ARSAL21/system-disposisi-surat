import type { ScreeningChecklistItem } from '@/types';

export const manualIntakeChecklist: ScreeningChecklistItem[] = [
    {
        id: 'sender',
        label: 'Identitas pengirim dapat diverifikasi',
        description: 'Nama instansi dan kontak pengirim tersedia pada surat.',
        checked: false,
    },
    {
        id: 'letter-metadata',
        label: 'Data surat konsisten',
        description: 'Nomor, tanggal, perihal, dan ringkasan sesuai dokumen.',
        checked: false,
    },
    {
        id: 'document',
        label: 'Hasil scan terbaca dan lengkap',
        description: 'Seluruh halaman terlihat jelas, utuh, dan berurutan.',
        checked: false,
    },
    {
        id: 'scope',
        label: 'Surat termasuk jalur pimpinan',
        description: 'Isi surat layak diteruskan melalui alur disposisi.',
        checked: false,
    },
];

export const manualIntakePreviewRoutes = {
    store: '/back-office/previews/intake/manual/create',
    intake_index: '/back-office/intake/submissions',
    incoming_register: '/back-office/previews/incoming-letters',
};

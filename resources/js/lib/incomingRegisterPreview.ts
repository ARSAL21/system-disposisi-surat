import type {
    IncomingRegisterFilters,
    IncomingRegisterItem,
    IncomingRegisterSummary,
} from '@/types';

export const previewIncomingRegisterFilters: IncomingRegisterFilters = {
    search: '',
    source: '',
    status: '',
    year: '',
    date_from: '2026-09-01',
    date_to: '2026-09-30',
};

export const previewIncomingRegisterSummary: IncomingRegisterSummary = {
    total_letters: 1842,
    online_letters: 1164,
    manual_letters: 678,
    received_today: 14,
};

export const previewIncomingRegisterItems: IncomingRegisterItem[] = [
    {
        id: 901,
        agenda_number: '0196/UMUM/IX/2026',
        agenda_year: 2026,
        source: 'MANUAL',
        received_at: '2026-09-04T01:42:00.000Z',
        sender_organization_name: 'Kelompok Tani Waborobo',
        contact_name: 'La Ode Karim',
        external_letter_number: '014/KT-WBR/IX/2026',
        external_letter_date: '2026-09-03',
        subject: 'Permohonan fasilitasi sarana produksi pertanian',
        status: 'REGISTERED',
        document: {
            original_filename: 'permohonan-kelompok-tani-waborobo.pdf',
            size_bytes: 2864000,
        },
        links: {
            document_history: '/back-office/previews/letters/901/documents',
        },
    },
    {
        id: 900,
        agenda_number: '0195/UMUM/IX/2026',
        agenda_year: 2026,
        source: 'ONLINE',
        received_at: '2026-09-04T01:18:00.000Z',
        sender_organization_name: 'Universitas Dayanu Ikhsanuddin',
        contact_name: 'Nur Rahmawati',
        external_letter_number: '221/UND/IX/2026',
        external_letter_date: '2026-09-02',
        subject: 'Undangan seminar pembangunan kawasan pesisir',
        status: 'ROUTED',
        document: {
            original_filename: 'undangan-seminar-pesisir.pdf',
            size_bytes: 1942000,
        },
        links: {
            document_history: '/back-office/previews/letters/900/documents',
        },
    },
    {
        id: 899,
        agenda_number: '0194/UMUM/IX/2026',
        agenda_year: 2026,
        source: 'MANUAL',
        received_at: '2026-09-03T07:05:00.000Z',
        sender_organization_name: 'Forum Anak Kota Baubau',
        contact_name: 'Sitti Aminah',
        external_letter_number: null,
        external_letter_date: null,
        subject: 'Permohonan audiensi program ruang ramah anak',
        status: 'IN_PROGRESS',
        document: {
            original_filename: 'permohonan-audiensi-forum-anak.pdf',
            size_bytes: 3281000,
        },
        links: {
            document_history: '/back-office/previews/letters/899/documents',
        },
    },
    {
        id: 898,
        agenda_number: '0193/UMUM/IX/2026',
        agenda_year: 2026,
        source: 'ONLINE',
        received_at: '2026-09-03T03:26:00.000Z',
        sender_organization_name: 'PT Pelabuhan Indonesia Regional IV',
        contact_name: 'Andi Firmansyah',
        external_letter_number: '089/PEL-REG4/2026',
        external_letter_date: '2026-09-01',
        subject: 'Koordinasi penataan akses kawasan pelabuhan',
        status: 'IN_PROGRESS',
        document: {
            original_filename: 'koordinasi-akses-pelabuhan.pdf',
            size_bytes: 4790000,
        },
        links: {
            document_history: '/back-office/previews/letters/898/documents',
        },
    },
    {
        id: 897,
        agenda_number: '0192/UMUM/IX/2026',
        agenda_year: 2026,
        source: 'MANUAL',
        received_at: '2026-09-02T06:47:00.000Z',
        sender_organization_name: 'Yayasan Pendidikan Al-Amanah',
        contact_name: 'Muhammad Rizal',
        external_letter_number: '031/YPA/IX/2026',
        external_letter_date: '2026-09-01',
        subject: 'Permohonan dukungan kegiatan literasi masyarakat',
        status: 'COMPLETED',
        document: {
            original_filename: 'permohonan-literasi-al-amanah.pdf',
            size_bytes: 2215000,
        },
        links: {
            document_history: '/back-office/previews/letters/897/documents',
        },
    },
    {
        id: 896,
        agenda_number: '0191/UMUM/IX/2026',
        agenda_year: 2026,
        source: 'ONLINE',
        received_at: '2026-09-01T02:12:00.000Z',
        sender_organization_name: 'Kamar Dagang dan Industri Baubau',
        contact_name: 'Hasan Basri',
        external_letter_number: '117/KADIN-BB/IX/2026',
        external_letter_date: '2026-08-31',
        subject: 'Usulan forum konsultasi kemudahan investasi daerah',
        status: 'ROUTED',
        document: {
            original_filename: 'usulan-forum-investasi.pdf',
            size_bytes: 3510000,
        },
        links: {
            document_history: '/back-office/previews/letters/896/documents',
        },
    },
];

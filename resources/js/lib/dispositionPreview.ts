import { previewLetterRoutingItems } from '@/lib/letterRoutingPreview';
import type {
    DispositionInboxItem,
    DispositionInboxSummary,
    DispositionInstructionLabel,
    DispositionInstructionLabelOption,
    DispositionPositionOption,
    FirstDispositionReceipt,
} from '@/types';

export const previewAssistantPositions: DispositionPositionOption[] = [
    {
        id: 21,
        code: 'ASISTEN_PEMERINTAHAN_KESRA',
        name: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
        level_code: 'ASSISTANT',
        unit_name: 'Sekretariat Daerah',
        holder_name: 'Drs. Abdul Malik, M.Si.',
        is_available: true,
    },
    {
        id: 22,
        code: 'ASISTEN_PEREKONOMIAN_PEMBANGUNAN',
        name: 'Asisten Perekonomian dan Pembangunan',
        level_code: 'ASSISTANT',
        unit_name: 'Sekretariat Daerah',
        holder_name: 'Ir. Fatmawati Yusuf, M.Si.',
        is_available: true,
    },
    {
        id: 23,
        code: 'ASISTEN_ADMINISTRASI_UMUM',
        name: 'Asisten Administrasi Umum',
        level_code: 'ASSISTANT',
        unit_name: 'Sekretariat Daerah',
        holder_name: null,
        is_available: false,
    },
];

export const previewSectionHeadPositions: DispositionPositionOption[] = [
    {
        id: 41,
        code: 'KABAG_PEMERINTAHAN',
        name: 'Kepala Bagian Pemerintahan',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Pemerintahan',
        holder_name: 'Drs. Arman Saleh, M.Si.',
        is_available: true,
    },
    {
        id: 42,
        code: 'KABAG_HUKUM',
        name: 'Kepala Bagian Hukum',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Hukum',
        holder_name: 'Nurlina, S.H., M.H.',
        is_available: true,
    },
    {
        id: 43,
        code: 'KABAG_PEREKONOMIAN',
        name: 'Kepala Bagian Perekonomian',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Perekonomian',
        holder_name: 'Rahmat Hidayat, S.E.',
        is_available: true,
    },
    {
        id: 44,
        code: 'KABAG_PEMBANGUNAN',
        name: 'Kepala Bagian Pembangunan',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Pembangunan',
        holder_name: 'Maya Sari, S.T., M.T.',
        is_available: true,
    },
    {
        id: 45,
        code: 'KABAG_KESRA',
        name: 'Kepala Bagian Kesejahteraan Rakyat',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Kesejahteraan Rakyat',
        holder_name: 'Abd. Karim, S.Ag., M.Pd.',
        is_available: true,
    },
    {
        id: 46,
        code: 'KABAG_UMUM',
        name: 'Kepala Bagian Umum',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Umum',
        holder_name: 'Hendra Wijaya, S.Sos.',
        is_available: true,
    },
    {
        id: 47,
        code: 'KABAG_ORGANISASI',
        name: 'Kepala Bagian Organisasi',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Organisasi',
        holder_name: 'Fitriani, S.IP., M.Si.',
        is_available: true,
    },
    {
        id: 48,
        code: 'KABAG_PROTOKOL',
        name: 'Kepala Bagian Protokol dan Komunikasi Pimpinan',
        level_code: 'SECTION_HEAD',
        unit_name: 'Bagian Protokol dan Komunikasi Pimpinan',
        holder_name: 'Yusuf Amin, S.Sos.',
        is_available: true,
    },
];

export const previewDispositionInstructionLabels: DispositionInstructionLabelOption[] =
    [
        {
            id: 31,
            code: 'UNTUK_DIKETAHUI',
            name: 'Untuk diketahui',
            description: 'Menjadi perhatian dan bahan informasi penerima.',
        },
        {
            id: 32,
            code: 'UNTUK_DITINDAKLANJUTI',
            name: 'Untuk ditindaklanjuti',
            description: 'Memerlukan tindakan sesuai kewenangan penerima.',
        },
        {
            id: 33,
            code: 'UNTUK_DIPELAJARI',
            name: 'Untuk dipelajari',
            description: 'Dipelajari sebelum menentukan langkah berikutnya.',
        },
        {
            id: 34,
            code: 'UNTUK_DIKOORDINASIKAN',
            name: 'Untuk dikoordinasikan',
            description: 'Koordinasikan substansi dengan unit terkait.',
        },
        {
            id: 35,
            code: 'SEGERA',
            name: 'Segera',
            description: 'Memerlukan perhatian dan tindak lanjut prioritas.',
        },
    ];

export const previewFirstDispositionReceipt: FirstDispositionReceipt = {
    status: 'PENDING',
    recipient_position: previewAssistantPositions[0],
    instructions: [
        { code: 'UNTUK_DITINDAKLANJUTI', name: 'Untuk ditindaklanjuti' },
        { code: 'SEGERA', name: 'Segera' },
    ],
    instruction_note:
        'Koordinasikan telaah awal dan siapkan opsi tindak lanjut untuk rapat pimpinan.',
    disposed_by: {
        name: 'Dr. H. Ahmad Darmawan, S.E., M.Si.',
        position: 'Wali Kota',
        unit: 'Pemerintah Kota',
    },
    disposed_at: '2026-08-31T11:08:00+08:00',
};

export const previewDispositionInboxItems: DispositionInboxItem[] = [
    {
        recipient_id: 701,
        letter: previewLetterRoutingItems[2],
        sender: previewFirstDispositionReceipt.disposed_by,
        recipient_position: previewAssistantPositions[0],
        instructions: previewFirstDispositionReceipt.instructions,
        instruction_note: previewFirstDispositionReceipt.instruction_note,
        status: 'PENDING',
        received_at: previewFirstDispositionReceipt.disposed_at,
        current_document: previewLetterRoutingItems[2].current_document,
        links: {
            show: '/back-office/previews/dispositions/inbox/recipients/701',
        },
    },
    {
        recipient_id: 702,
        letter: previewLetterRoutingItems[3],
        sender: {
            name: 'Ir. Nurhayati Rahman, M.Si.',
            position: 'Sekretaris Daerah',
            unit: 'Sekretariat Daerah',
        },
        recipient_position: previewAssistantPositions[1],
        instructions: [
            { code: 'UNTUK_DIPELAJARI', name: 'Untuk dipelajari' },
            {
                code: 'UNTUK_DIKOORDINASIKAN',
                name: 'Untuk dikoordinasikan',
            },
        ],
        instruction_note: null,
        status: 'IN_PROGRESS',
        received_at: '2026-08-30T14:26:00+08:00',
        current_document: previewLetterRoutingItems[3].current_document,
        links: {
            show: '/back-office/previews/dispositions/inbox/recipients/702',
        },
    },
];

export const previewDispositionInboxSummary: DispositionInboxSummary = {
    pending: 5,
    in_progress: 2,
    received_today: 3,
};

export const previewInstructionLabels: DispositionInstructionLabel[] = [
    ...previewDispositionInstructionLabels.map((label, index) => ({
        ...label,
        sort_order: (index + 1) * 10,
        is_active: true,
        created_at: '2026-08-01T08:00:00+08:00',
        updated_at: '2026-08-20T09:30:00+08:00',
        links: {
            update: `#update-instruction-${label.id}`,
            status: `#status-instruction-${label.id}`,
        },
    })),
    {
        id: 36,
        code: 'UNTUK_DIARSIPKAN',
        name: 'Untuk diarsipkan',
        description: 'Label lama yang tidak lagi dipakai pada disposisi baru.',
        sort_order: 60,
        is_active: false,
        created_at: '2026-08-01T08:00:00+08:00',
        updated_at: '2026-08-18T16:15:00+08:00',
        links: {
            update: '#update-instruction-36',
            status: '#status-instruction-36',
        },
    },
];

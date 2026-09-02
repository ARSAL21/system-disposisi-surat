import { previewLetterRoutingItems } from '@/lib/letterRoutingPreview';
import type {
    AssistantBranchMonitor,
    DispositionBranchLifecycle,
    DispositionInboxItem,
    DispositionInboxSummary,
    DispositionInstructionLabel,
    DispositionInstructionLabelOption,
    DispositionPositionOption,
    FirstDispositionReceipt,
    ForwardDispositionReceipt,
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
    recipients: [
        {
            status: 'PENDING',
            recipient_position: previewAssistantPositions[0],
        },
    ],
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
        status: 'COMPLETED',
        received_at: '2026-08-30T14:26:00+08:00',
        current_document: previewLetterRoutingItems[3].current_document,
        links: {
            show: '/back-office/previews/dispositions/inbox/recipients/702',
        },
    },
    {
        recipient_id: 703,
        letter: previewLetterRoutingItems[3],
        sender: {
            name: 'Drs. Abdul Malik, M.Si.',
            position: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
            unit: 'Sekretariat Daerah',
        },
        recipient_position: previewSectionHeadPositions[2],
        instructions: [
            {
                code: 'UNTUK_DITINDAKLANJUTI',
                name: 'Untuk ditindaklanjuti',
            },
            { code: 'SEGERA', name: 'Segera' },
        ],
        instruction_note:
            'Siapkan telaah dampak ekonomi dan koordinasikan data pendukung dengan perangkat daerah terkait.',
        status: 'PENDING',
        received_at: '2026-09-01T10:15:00+08:00',
        current_document: previewLetterRoutingItems[3].current_document,
        links: {
            show: '/back-office/previews/dispositions/inbox/recipients/703',
        },
    },
    {
        recipient_id: 704,
        letter: previewLetterRoutingItems[2],
        sender: {
            name: 'Drs. Abdul Malik, M.Si.',
            position: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
            unit: 'Sekretariat Daerah',
        },
        recipient_position: previewSectionHeadPositions[1],
        instructions: [
            { code: 'UNTUK_DIPELAJARI', name: 'Untuk dipelajari' },
            {
                code: 'UNTUK_DIKOORDINASIKAN',
                name: 'Untuk dikoordinasikan',
            },
        ],
        instruction_note:
            'Telaah dasar hukum dan siapkan poin yang memerlukan keputusan pimpinan.',
        status: 'IN_PROGRESS',
        received_at: '2026-09-01T08:40:00+08:00',
        current_document: previewLetterRoutingItems[2].current_document,
        links: {
            show: '/back-office/previews/dispositions/inbox/recipients/704',
        },
    },
    {
        recipient_id: 705,
        letter: previewLetterRoutingItems[2],
        sender: {
            name: 'Drs. Abdul Malik, M.Si.',
            position: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
            unit: 'Sekretariat Daerah',
        },
        recipient_position: previewSectionHeadPositions[0],
        instructions: [
            {
                code: 'UNTUK_DITINDAKLANJUTI',
                name: 'Untuk ditindaklanjuti',
            },
        ],
        instruction_note:
            'Koordinasikan jadwal dan kesiapan bahan bersama unit terkait.',
        status: 'COMPLETED',
        received_at: '2026-08-31T09:10:00+08:00',
        current_document: previewLetterRoutingItems[2].current_document,
        links: {
            show: '/back-office/previews/dispositions/inbox/recipients/705',
        },
    },
];

const previewBranchActors = {
    pemerintahan: {
        name: 'Drs. Arman Saleh, M.Si.',
        position: 'Kepala Bagian Pemerintahan',
        unit: 'Bagian Pemerintahan',
    },
    hukum: {
        name: 'Nurlina, S.H., M.H.',
        position: 'Kepala Bagian Hukum',
        unit: 'Bagian Hukum',
    },
};

export const previewDispositionBranches: Record<
    number,
    DispositionBranchLifecycle
> = {
    703: {
        status: 'PENDING',
        received_at: '2026-09-01T10:15:00+08:00',
        started_at: null,
        completed_at: null,
        completion_note: null,
        completed_by: null,
        follow_ups: [],
    },
    704: {
        status: 'IN_PROGRESS',
        received_at: '2026-09-01T08:40:00+08:00',
        started_at: '2026-09-01T09:05:00+08:00',
        completed_at: null,
        completion_note: null,
        completed_by: null,
        follow_ups: [
            {
                note: 'Telaah awal telah dilakukan. Dua pasal memerlukan konfirmasi dari perangkat daerah pengusul.',
                created_at: '2026-09-01T09:42:00+08:00',
                created_by: previewBranchActors.hukum,
            },
            {
                note: 'Bahan klarifikasi sudah diterima dan sedang diselaraskan dengan regulasi terbaru.',
                created_at: '2026-09-01T11:18:00+08:00',
                created_by: previewBranchActors.hukum,
            },
        ],
    },
    705: {
        status: 'COMPLETED',
        received_at: '2026-08-31T09:10:00+08:00',
        started_at: '2026-08-31T09:32:00+08:00',
        completed_at: '2026-09-01T08:25:00+08:00',
        completion_note:
            'Koordinasi jadwal telah selesai. Bahan rapat dan daftar peserta sudah diteruskan kepada sekretariat pimpinan.',
        completed_by: previewBranchActors.pemerintahan,
        follow_ups: [
            {
                note: 'Konfirmasi awal dengan tiga unit terkait telah dilakukan.',
                created_at: '2026-08-31T11:20:00+08:00',
                created_by: previewBranchActors.pemerintahan,
            },
            {
                note: 'Jadwal final dan bahan rapat telah disepakati bersama.',
                created_at: '2026-09-01T08:10:00+08:00',
                created_by: previewBranchActors.pemerintahan,
            },
        ],
    },
};

export const previewForwardedDispositionReceipt: ForwardDispositionReceipt = {
    instructions: [
        { code: 'UNTUK_DITINDAKLANJUTI', name: 'Untuk ditindaklanjuti' },
        {
            code: 'UNTUK_DIKOORDINASIKAN',
            name: 'Untuk dikoordinasikan',
        },
    ],
    instruction_note:
        'Kerjakan sesuai kewenangan masing-masing dan laporkan perkembangan melalui jurnal cabang.',
    recipients: [
        {
            recipient_position: previewSectionHeadPositions[0],
            status: previewDispositionBranches[705].status,
            received_at: previewDispositionBranches[705].received_at,
        },
        {
            recipient_position: previewSectionHeadPositions[1],
            status: previewDispositionBranches[704].status,
            received_at: previewDispositionBranches[704].received_at,
        },
        {
            recipient_position: previewSectionHeadPositions[2],
            status: previewDispositionBranches[703].status,
            received_at: previewDispositionBranches[703].received_at,
        },
    ],
    disposed_by: {
        name: 'Drs. Abdul Malik, M.Si.',
        position: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
        unit: 'Sekretariat Daerah',
    },
    disposed_at: '2026-09-01T08:35:00+08:00',
};

export const previewAssistantBranchMonitor: AssistantBranchMonitor = {
    total: 3,
    pending: 1,
    in_progress: 1,
    completed: 1,
    percent_complete: 33,
    branches: [
        {
            recipient_position: previewSectionHeadPositions[0],
            ...previewDispositionBranches[705],
        },
        {
            recipient_position: previewSectionHeadPositions[1],
            ...previewDispositionBranches[704],
        },
        {
            recipient_position: previewSectionHeadPositions[2],
            ...previewDispositionBranches[703],
        },
    ],
};

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

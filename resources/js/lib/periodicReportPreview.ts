import type {
    PaginatedPeriodicReportLetters,
    PeriodicReportFilters,
    PeriodicReportIntakeFunnel,
    PeriodicReportOrganizationGraph,
    PeriodicReportPerformance,
    PeriodicReportSenderBreakdown,
    PeriodicReportScope,
    PeriodicReportSourceBreakdown,
    PeriodicReportSummary,
    PeriodicReportTrendPoint,
    ReportAggregateGraphNode,
    ReportProcessDetail,
} from '@/types';

export const previewPeriodicReportFilters: PeriodicReportFilters = {
    date_from: '2026-09-01',
    date_to: '2026-09-30',
    source: '',
    event: 'RECEIVED',
    status: '',
    search: '',
};

export const previewPeriodicReportScope: PeriodicReportScope = {
    mode: 'CITYWIDE',
    label: 'Cakupan seluruh kota',
    description:
        'Ringkasan lintas Asisten dan Kepala Bagian sesuai kewenangan pimpinan aktif.',
};

export const previewPeriodicReportSummary: PeriodicReportSummary = {
    received_letters: 184,
    processing_started: 157,
    completed_letters: 126,
    average_completion_hours: 31.6,
};

export const previewPeriodicReportIntakeFunnel: PeriodicReportIntakeFunnel = {
    online_submissions: 142,
    manual_submissions: 71,
    converted_to_letters: 184,
};

export const previewPeriodicReportSenderBreakdown: PeriodicReportSenderBreakdown[] =
    [
        { name: 'Kementerian Dalam Negeri', total: 19, percent: 100 },
        {
            name: 'Pemerintah Provinsi Sulawesi Tenggara',
            total: 15,
            percent: 79,
        },
        { name: 'Bappeda Kota Baubau', total: 12, percent: 63 },
        { name: 'Ombudsman Republik Indonesia', total: 8, percent: 42 },
    ];

export const previewPeriodicReportSourceBreakdown: PeriodicReportSourceBreakdown[] =
    [
        {
            source: 'ONLINE',
            label: 'Pengajuan online',
            total: 121,
            percent: 66,
        },
        {
            source: 'MANUAL',
            label: 'Penerimaan manual',
            total: 63,
            percent: 34,
        },
    ];

export const previewPeriodicReportTrend: PeriodicReportTrendPoint[] = [
    { label: '1 Sep', received: 18, processing_started: 13, completed: 8 },
    { label: '5 Sep', received: 24, processing_started: 19, completed: 15 },
    { label: '9 Sep', received: 19, processing_started: 17, completed: 13 },
    { label: '13 Sep', received: 32, processing_started: 26, completed: 21 },
    { label: '17 Sep', received: 27, processing_started: 24, completed: 20 },
    { label: '21 Sep', received: 38, processing_started: 31, completed: 26 },
    { label: '25 Sep', received: 26, processing_started: 27, completed: 23 },
];

export const previewAssistantPerformance: PeriodicReportPerformance[] = [
    {
        position_code: 'ASISTEN-I',
        position_name: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
        official_name: 'Drs. Abdul Malik, M.Si.',
        unit_name: 'Sekretariat Daerah',
        level_code: 'ASSISTANT',
        assigned: 68,
        pending: 4,
        in_progress: 9,
        completed: 55,
        completion_percent: 81,
        average_completion_hours: 7.8,
    },
    {
        position_code: 'ASISTEN-II',
        position_name: 'Asisten Perekonomian dan Pembangunan',
        official_name: 'Ir. Fatmawati Yusuf, M.Si.',
        unit_name: 'Sekretariat Daerah',
        level_code: 'ASSISTANT',
        assigned: 61,
        pending: 6,
        in_progress: 12,
        completed: 43,
        completion_percent: 70,
        average_completion_hours: 9.4,
    },
    {
        position_code: 'ASISTEN-III',
        position_name: 'Asisten Administrasi Umum',
        official_name: 'Dra. Nur Aisyah, M.Si.',
        unit_name: 'Sekretariat Daerah',
        level_code: 'ASSISTANT',
        assigned: 55,
        pending: 3,
        in_progress: 8,
        completed: 44,
        completion_percent: 80,
        average_completion_hours: 6.9,
    },
];

export const previewSectionHeadPerformance: PeriodicReportPerformance[] = [
    {
        position_code: 'KABAG_HUKUM',
        position_name: 'Kepala Bagian Hukum',
        official_name: 'Nurlina, S.H., M.H.',
        unit_name: 'Bagian Hukum',
        level_code: 'SECTION_HEAD',
        assigned: 29,
        pending: 2,
        in_progress: 4,
        completed: 23,
        completion_percent: 79,
        average_completion_hours: 26.4,
    },
    {
        position_code: 'KABAG_TAPEM',
        position_name: 'Kepala Bagian Tata Pemerintahan',
        official_name: 'Drs. Arman Saleh, M.Si.',
        unit_name: 'Bagian Tata Pemerintahan',
        level_code: 'SECTION_HEAD',
        assigned: 27,
        pending: 1,
        in_progress: 3,
        completed: 23,
        completion_percent: 85,
        average_completion_hours: 23.1,
    },
    {
        position_code: 'KABAG_KESRA',
        position_name: 'Kepala Bagian Kesejahteraan Rakyat',
        official_name: 'Abd. Karim, S.Ag., M.Pd.',
        unit_name: 'Bagian Kesejahteraan Rakyat',
        level_code: 'SECTION_HEAD',
        assigned: 24,
        pending: 3,
        in_progress: 5,
        completed: 16,
        completion_percent: 67,
        average_completion_hours: 38.7,
    },
    {
        position_code: 'KABAG_UMUM',
        position_name: 'Kepala Bagian Umum',
        official_name: 'Hendra Wijaya, S.Sos.',
        unit_name: 'Bagian Umum',
        level_code: 'SECTION_HEAD',
        assigned: 21,
        pending: 1,
        in_progress: 2,
        completed: 18,
        completion_percent: 86,
        average_completion_hours: 19.8,
    },
];

export const previewPeriodicReportLetters: PaginatedPeriodicReportLetters = {
    data: [
        {
            reference: 'surat-agd-2026-00918',
            agenda_number: 'AGD/2026/00918',
            subject: 'Permohonan harmonisasi rancangan peraturan wali kota',
            sender_organization_name: 'Bagian Hukum Provinsi Sulawesi Tenggara',
            source: 'ONLINE',
            status: 'IN_PROGRESS',
            received_at: '2026-09-21T08:20:00+08:00',
            processing_started_at: '2026-09-21T09:05:00+08:00',
            completed_at: null,
            turnaround_hours: null,
            participant_position_codes: [
                'SEKDA',
                'ASISTEN-I',
                'KABAG_HUKUM',
                'KABAG_TAPEM',
                'KABAG_ORGANISASI',
            ],
            branch_progress: {
                total: 3,
                pending: 0,
                in_progress: 2,
                completed: 1,
                percent_complete: 33,
            },
            links: {
                detail: '/back-office/previews/reports/letters/surat-agd-2026-00918',
            },
        },
        {
            reference: 'surat-agd-2026-00897',
            agenda_number: 'AGD/2026/00897',
            subject: 'Koordinasi persiapan forum perangkat daerah tahun 2027',
            sender_organization_name: 'Bappeda Kota Baubau',
            source: 'MANUAL',
            status: 'COMPLETED',
            received_at: '2026-09-17T10:12:00+08:00',
            processing_started_at: '2026-09-17T11:24:00+08:00',
            completed_at: '2026-09-18T15:40:00+08:00',
            turnaround_hours: 29.5,
            participant_position_codes: [
                'WALI_KOTA',
                'ASISTEN-II',
                'KABAG_EKONOMI',
                'KABAG_PEMBANGUNAN',
            ],
            branch_progress: {
                total: 2,
                pending: 0,
                in_progress: 0,
                completed: 2,
                percent_complete: 100,
            },
            links: {
                detail: '/back-office/previews/reports/letters/surat-agd-2026-00897',
            },
        },
        {
            reference: 'surat-agd-2026-00863',
            agenda_number: 'AGD/2026/00863',
            subject: 'Permintaan data dukung evaluasi pelayanan publik',
            sender_organization_name: 'Ombudsman Republik Indonesia',
            source: 'ONLINE',
            status: 'IN_PROGRESS',
            received_at: '2026-09-12T13:42:00+08:00',
            processing_started_at: '2026-09-13T08:10:00+08:00',
            completed_at: null,
            turnaround_hours: null,
            participant_position_codes: [
                'SEKDA',
                'ASISTEN-III',
                'KABAG_UMUM',
                'KABAG_ORGANISASI',
                'KABAG_PROTOCOLER',
            ],
            branch_progress: {
                total: 4,
                pending: 1,
                in_progress: 1,
                completed: 2,
                percent_complete: 50,
            },
            links: {
                detail: '/back-office/previews/reports/letters/surat-agd-2026-00863',
            },
        },
        {
            reference: 'surat-agd-2026-00831',
            agenda_number: 'AGD/2026/00831',
            subject: 'Undangan rapat koordinasi pengendalian inflasi daerah',
            sender_organization_name: 'Kementerian Dalam Negeri',
            source: 'ONLINE',
            status: 'COMPLETED',
            received_at: '2026-09-08T09:04:00+08:00',
            processing_started_at: '2026-09-08T09:50:00+08:00',
            completed_at: '2026-09-09T11:18:00+08:00',
            turnaround_hours: 26.2,
            participant_position_codes: [
                'WALI_KOTA',
                'ASISTEN-I',
                'KABAG_TAPEM',
                'KABAG_KESRA',
                'KABAG_HUKUM',
            ],
            branch_progress: {
                total: 3,
                pending: 0,
                in_progress: 0,
                completed: 3,
                percent_complete: 100,
            },
            links: {
                detail: '/back-office/previews/reports/letters/surat-agd-2026-00831',
            },
        },
    ],
    pagination: {
        current_page: 1,
        last_page: 1,
        from: 1,
        to: 4,
        total: 4,
        previous_url: null,
        next_url: null,
    },
};

const executiveActor = {
    name: 'Ir. Nurhayati Rahman, M.Si.',
    position: 'Sekretaris Daerah',
    unit: 'Sekretariat Daerah',
};

const assistantActor = {
    name: 'Drs. Abdul Malik, M.Si.',
    position: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
    unit: 'Sekretariat Daerah',
};

export const previewReportProcessDetail: ReportProcessDetail = {
    letter: {
        reference: 'surat-agd-2026-00918',
        agenda_number: 'AGD/2026/00918',
        subject: 'Permohonan harmonisasi rancangan peraturan wali kota',
        sender_organization_name: 'Bagian Hukum Provinsi Sulawesi Tenggara',
        external_letter_number: '180/417/BH/IX/2026',
        source: 'ONLINE',
        status: 'IN_PROGRESS',
        received_at: '2026-09-21T08:20:00+08:00',
        completed_at: null,
    },
    initial_route: {
        target_position: {
            code: 'SEKDA',
            name: 'Sekretaris Daerah',
            unit_name: 'Sekretariat Daerah',
            official_name: executiveActor.name,
        },
        routed_by: {
            name: 'Hendra Wijaya, S.Sos.',
            position: 'Kepala Bagian Umum',
            unit: 'Bagian Umum',
        },
        routed_at: '2026-09-21T08:47:00+08:00',
    },
    sekda_handoff: null,
    branches: [
        {
            reference: 'assistant-branch-01',
            recipient_position: {
                code: 'ASISTEN-I',
                name: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
                unit_name: 'Sekretariat Daerah',
                official_name: assistantActor.name,
            },
            status: 'COMPLETED',
            received_at: '2026-09-21T09:05:00+08:00',
            forwarded_at: '2026-09-21T09:38:00+08:00',
            instructions: [
                { code: 'UNTUK_DITINDAKLANJUTI', name: 'Tindak lanjuti' },
                { code: 'SEGERA', name: 'Segera' },
            ],
            instruction_note:
                'Koordinasikan harmonisasi lintas bagian dan sampaikan hasil telaah kepada Sekda.',
            disposed_by: executiveActor,
            disposed_at: '2026-09-21T09:05:00+08:00',
            children: [
                {
                    reference: 'section-branch-hukum',
                    recipient_position: {
                        code: 'KABAG_HUKUM',
                        name: 'Kepala Bagian Hukum',
                        unit_name: 'Bagian Hukum',
                        official_name: 'Nurlina, S.H., M.H.',
                    },
                    status: 'IN_PROGRESS',
                    received_at: '2026-09-21T09:38:00+08:00',
                    started_at: '2026-09-21T10:02:00+08:00',
                    completed_at: null,
                    instructions: [
                        { code: 'UNTUK_DIPELAJARI', name: 'Pelajari' },
                        {
                            code: 'UNTUK_DIKOORDINASIKAN',
                            name: 'Koordinasikan',
                        },
                    ],
                    instruction_note:
                        'Periksa konsistensi dasar hukum dan matriks perubahan pasal.',
                    disposed_by: assistantActor,
                    disposed_at: '2026-09-21T09:38:00+08:00',
                    follow_ups: [
                        {
                            note: 'Matriks harmonisasi telah disusun. Dua pasal masih memerlukan konfirmasi dari perangkat daerah pengusul.',
                            created_at: '2026-09-21T13:26:00+08:00',
                            created_by: {
                                name: 'Nurlina, S.H., M.H.',
                                position: 'Kepala Bagian Hukum',
                                unit: 'Bagian Hukum',
                            },
                        },
                        {
                            note: 'Konfirmasi perangkat daerah telah diterima dan sedang dimasukkan ke naskah hasil telaah.',
                            created_at: '2026-09-22T09:14:00+08:00',
                            created_by: {
                                name: 'Nurlina, S.H., M.H.',
                                position: 'Kepala Bagian Hukum',
                                unit: 'Bagian Hukum',
                            },
                        },
                    ],
                    completion_note: null,
                    completed_by: null,
                    attention: {
                        needs_attention: false,
                        idle_hours: 23,
                        reason: null,
                    },
                },
                {
                    reference: 'section-branch-tapem',
                    recipient_position: {
                        code: 'KABAG_TAPEM',
                        name: 'Kepala Bagian Tata Pemerintahan',
                        unit_name: 'Bagian Tata Pemerintahan',
                        official_name: 'Drs. Arman Saleh, M.Si.',
                    },
                    status: 'COMPLETED',
                    received_at: '2026-09-21T09:38:00+08:00',
                    started_at: '2026-09-21T09:52:00+08:00',
                    completed_at: '2026-09-21T16:24:00+08:00',
                    instructions: [
                        {
                            code: 'UNTUK_DIKOORDINASIKAN',
                            name: 'Koordinasikan',
                        },
                    ],
                    instruction_note:
                        'Pastikan pembagian urusan pemerintahan telah sesuai.',
                    disposed_by: assistantActor,
                    disposed_at: '2026-09-21T09:38:00+08:00',
                    follow_ups: [
                        {
                            note: 'Substansi pembagian urusan sudah dibandingkan dengan matriks kewenangan terbaru.',
                            created_at: '2026-09-21T14:08:00+08:00',
                            created_by: {
                                name: 'Drs. Arman Saleh, M.Si.',
                                position: 'Kepala Bagian Tata Pemerintahan',
                                unit: 'Bagian Tata Pemerintahan',
                            },
                        },
                    ],
                    completion_note:
                        'Telaah urusan pemerintahan selesai. Tidak ditemukan pertentangan pembagian kewenangan.',
                    completed_by: {
                        name: 'Drs. Arman Saleh, M.Si.',
                        position: 'Kepala Bagian Tata Pemerintahan',
                        unit: 'Bagian Tata Pemerintahan',
                    },
                    attention: {
                        needs_attention: false,
                        idle_hours: null,
                        reason: null,
                    },
                },
                {
                    reference: 'section-branch-organisasi',
                    recipient_position: {
                        code: 'KABAG_ORGANISASI',
                        name: 'Kepala Bagian Organisasi',
                        unit_name: 'Bagian Organisasi',
                        official_name: 'Fitriani, S.IP., M.Si.',
                    },
                    status: 'IN_PROGRESS',
                    received_at: '2026-09-21T09:38:00+08:00',
                    started_at: '2026-09-21T10:18:00+08:00',
                    completed_at: null,
                    instructions: [
                        { code: 'UNTUK_DIPELAJARI', name: 'Pelajari' },
                    ],
                    instruction_note:
                        'Telaah implikasi rancangan terhadap struktur organisasi.',
                    disposed_by: assistantActor,
                    disposed_at: '2026-09-21T09:38:00+08:00',
                    follow_ups: [],
                    completion_note: null,
                    completed_by: null,
                    attention: {
                        needs_attention: true,
                        idle_hours: 51,
                        reason: 'Cabang belum memiliki pembaruan selama 51 jam.',
                    },
                },
            ],
            attention: {
                needs_attention: true,
                idle_hours: 51,
                reason: 'Satu cabang turunan belum diperbarui selama 51 jam.',
            },
        },
    ],
    progress: {
        total: 3,
        pending: 0,
        in_progress: 2,
        completed: 1,
        percent_complete: 33,
    },
    visibility_note:
        'Tampilan pimpinan memperlihatkan seluruh cabang, pelaksana, instruksi, jurnal, dan hasil penyelesaian surat ini.',
};

type PreviewAggregateNode = Omit<
    ReportAggregateGraphNode,
    'level' | 'children'
> & {
    level?: ReportAggregateGraphNode['level'];
    children?: PreviewAggregateNode[];
};

function aggregateLevel(
    node: PreviewAggregateNode,
): ReportAggregateGraphNode['level'] {
    if (node.level) {
        return node.level;
    }

    if (node.recipient_position.code === 'WALI_KOTA') {
        return 'MAYOR';
    }

    if (node.recipient_position.code === 'SEKDA') {
        return 'REGIONAL_SECRETARY';
    }

    return node.recipient_position.code.startsWith('ASISTEN')
        ? 'ASSISTANT'
        : 'SECTION_HEAD';
}

function presentAggregateNode(
    node: PreviewAggregateNode,
): ReportAggregateGraphNode {
    const level = aggregateLevel(node);
    const children = (node.children ?? []).map(presentAggregateNode);

    // Older preview fixtures placed Assistants directly below Wali Kota. Keep
    // preview aligned with the production graph by showing the required Sekda
    // handoff as an explicit intermediate node.
    if (
        level === 'MAYOR' &&
        children.some((child) => child.level === 'ASSISTANT')
    ) {
        return {
            ...node,
            level,
            children: [
                {
                    reference: `${node.reference}-sekda`,
                    recipient_position: {
                        code: 'SEKDA',
                        name: 'Sekretaris Daerah',
                        unit_name: 'Sekretariat Daerah',
                        official_name: node.recipient_position.official_name,
                    },
                    level: 'REGIONAL_SECRETARY',
                    progress: node.progress,
                    last_activity_at: node.last_activity_at,
                    average_completion_hours: node.average_completion_hours,
                    attention: node.attention,
                    children,
                },
            ],
        };
    }

    return {
        ...node,
        level,
        children,
    };
}

const previewPeriodicReportOrganizationGraphSeed: {
    generated_at: string;
    executives: PreviewAggregateNode[];
} = {
    generated_at: '2026-09-30T16:00:00+08:00',
    executives: [
        {
            reference: 'aggregate-wali-kota',
            recipient_position: {
                code: 'WALI_KOTA',
                name: 'Wali Kota',
                unit_name: 'Pemerintah Kota Baubau',
                official_name: 'Dr. H. Ahmad Darmawan, S.E., M.Si.',
            },
            progress: {
                total: 79,
                pending: 5,
                in_progress: 17,
                completed: 57,
                percent_complete: 72,
            },
            last_activity_at: '2026-09-30T15:42:00+08:00',
            attention: {
                needs_attention: true,
                idle_hours: 55,
                reason: 'Terdapat 2 cabang tanpa aktivitas lebih dari 48 jam.',
            },
            children: [
                {
                    reference: 'aggregate-wali-asisten-i',
                    recipient_position: {
                        code: 'ASISTEN-I',
                        name: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
                        unit_name: 'Sekretariat Daerah',
                        official_name: 'Drs. Abdul Malik, M.Si.',
                    },
                    progress: {
                        total: 28,
                        pending: 1,
                        in_progress: 5,
                        completed: 22,
                        percent_complete: 79,
                    },
                    last_activity_at: '2026-09-30T15:42:00+08:00',
                    attention: {
                        needs_attention: false,
                        idle_hours: 3,
                        reason: null,
                    },
                    children: [
                        {
                            reference: 'aggregate-wali-asisten-i-tapem',
                            recipient_position: {
                                code: 'KABAG_TAPEM',
                                name: 'Kepala Bagian Tata Pemerintahan',
                                unit_name: 'Bagian Tata Pemerintahan',
                                official_name: 'Drs. Arman Saleh, M.Si.',
                            },
                            progress: {
                                total: 15,
                                pending: 0,
                                in_progress: 2,
                                completed: 13,
                                percent_complete: 87,
                            },
                            last_activity_at: '2026-09-30T14:22:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 4,
                                reason: null,
                            },
                        },
                        {
                            reference: 'aggregate-wali-asisten-i-kesra',
                            recipient_position: {
                                code: 'KABAG_KESRA',
                                name: 'Kepala Bagian Kesejahteraan Rakyat',
                                unit_name: 'Bagian Kesejahteraan Rakyat',
                                official_name: 'Abd. Karim, S.Ag., M.Pd.',
                            },
                            progress: {
                                total: 13,
                                pending: 1,
                                in_progress: 3,
                                completed: 9,
                                percent_complete: 69,
                            },
                            last_activity_at: '2026-09-28T07:30:00+08:00',
                            attention: {
                                needs_attention: true,
                                idle_hours: 56,
                                reason: 'Satu cabang belum bergerak selama 56 jam.',
                            },
                        },
                    ],
                },
                {
                    reference: 'aggregate-wali-asisten-ii',
                    recipient_position: {
                        code: 'ASISTEN-II',
                        name: 'Asisten Perekonomian dan Pembangunan',
                        unit_name: 'Sekretariat Daerah',
                        official_name: 'Ir. Fatmawati Yusuf, M.Si.',
                    },
                    progress: {
                        total: 27,
                        pending: 2,
                        in_progress: 6,
                        completed: 19,
                        percent_complete: 70,
                    },
                    last_activity_at: '2026-09-30T13:12:00+08:00',
                    attention: {
                        needs_attention: false,
                        idle_hours: 6,
                        reason: null,
                    },
                    children: [
                        {
                            reference: 'aggregate-wali-asisten-ii-ekonomi',
                            recipient_position: {
                                code: 'KABAG_EKONOMI',
                                name: 'Kepala Bagian Ekonomi',
                                unit_name: 'Bagian Ekonomi',
                                official_name: 'Rahmat Hidayat, S.E.',
                            },
                            progress: {
                                total: 14,
                                pending: 1,
                                in_progress: 3,
                                completed: 10,
                                percent_complete: 71,
                            },
                            last_activity_at: '2026-09-30T13:12:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 6,
                                reason: null,
                            },
                        },
                        {
                            reference: 'aggregate-wali-asisten-ii-pembangunan',
                            recipient_position: {
                                code: 'KABAG_PEMBANGUNAN',
                                name: 'Kepala Bagian Pembangunan',
                                unit_name: 'Bagian Pembangunan',
                                official_name: 'Maya Sari, S.T., M.T.',
                            },
                            progress: {
                                total: 13,
                                pending: 1,
                                in_progress: 3,
                                completed: 9,
                                percent_complete: 69,
                            },
                            last_activity_at: '2026-09-29T16:48:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 26,
                                reason: null,
                            },
                        },
                    ],
                },
                {
                    reference: 'aggregate-wali-asisten-iii',
                    recipient_position: {
                        code: 'ASISTEN-III',
                        name: 'Asisten Administrasi Umum',
                        unit_name: 'Sekretariat Daerah',
                        official_name: 'Dra. Nur Aisyah, M.Si.',
                    },
                    progress: {
                        total: 24,
                        pending: 2,
                        in_progress: 6,
                        completed: 16,
                        percent_complete: 67,
                    },
                    last_activity_at: '2026-09-30T11:05:00+08:00',
                    attention: {
                        needs_attention: true,
                        idle_hours: 52,
                        reason: 'Satu cabang belum diperbarui selama 52 jam.',
                    },
                    children: [
                        {
                            reference: 'aggregate-wali-asisten-iii-umum',
                            recipient_position: {
                                code: 'KABAG_UMUM',
                                name: 'Kepala Bagian Umum',
                                unit_name: 'Bagian Umum',
                                official_name: 'Hendra Wijaya, S.Sos.',
                            },
                            progress: {
                                total: 12,
                                pending: 1,
                                in_progress: 2,
                                completed: 9,
                                percent_complete: 75,
                            },
                            last_activity_at: '2026-09-30T11:05:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 8,
                                reason: null,
                            },
                        },
                        {
                            reference: 'aggregate-wali-asisten-iii-organisasi',
                            recipient_position: {
                                code: 'KABAG_ORGANISASI',
                                name: 'Kepala Bagian Organisasi',
                                unit_name: 'Bagian Organisasi',
                                official_name: 'Fitriani, S.IP., M.Si.',
                            },
                            progress: {
                                total: 12,
                                pending: 1,
                                in_progress: 4,
                                completed: 7,
                                percent_complete: 58,
                            },
                            last_activity_at: '2026-09-28T11:04:00+08:00',
                            attention: {
                                needs_attention: true,
                                idle_hours: 52,
                                reason: 'Satu cabang belum diperbarui selama 52 jam.',
                            },
                        },
                    ],
                },
            ],
        },
        {
            reference: 'aggregate-sekda',
            recipient_position: {
                code: 'SEKDA',
                name: 'Sekretaris Daerah',
                unit_name: 'Sekretariat Daerah',
                official_name: executiveActor.name,
            },
            progress: {
                total: 105,
                pending: 8,
                in_progress: 20,
                completed: 77,
                percent_complete: 73,
            },
            last_activity_at: '2026-09-30T15:58:00+08:00',
            attention: {
                needs_attention: true,
                idle_hours: 50,
                reason: 'Terdapat 3 cabang tanpa aktivitas lebih dari 48 jam.',
            },
            children: [
                {
                    reference: 'aggregate-sekda-asisten-i',
                    recipient_position: {
                        code: 'ASISTEN-I',
                        name: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
                        unit_name: 'Sekretariat Daerah',
                        official_name: assistantActor.name,
                    },
                    progress: {
                        total: 40,
                        pending: 3,
                        in_progress: 7,
                        completed: 30,
                        percent_complete: 75,
                    },
                    last_activity_at: '2026-09-30T15:58:00+08:00',
                    attention: {
                        needs_attention: false,
                        idle_hours: 2,
                        reason: null,
                    },
                    children: [
                        {
                            reference: 'aggregate-sekda-asisten-i-hukum',
                            recipient_position: {
                                code: 'KABAG_HUKUM',
                                name: 'Kepala Bagian Hukum',
                                unit_name: 'Bagian Hukum',
                                official_name: 'Nurlina, S.H., M.H.',
                            },
                            progress: {
                                total: 22,
                                pending: 1,
                                in_progress: 4,
                                completed: 17,
                                percent_complete: 77,
                            },
                            last_activity_at: '2026-09-30T15:58:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 2,
                                reason: null,
                            },
                        },
                        {
                            reference: 'aggregate-sekda-asisten-i-tapem',
                            recipient_position: {
                                code: 'KABAG_TAPEM',
                                name: 'Kepala Bagian Tata Pemerintahan',
                                unit_name: 'Bagian Tata Pemerintahan',
                                official_name: 'Drs. Arman Saleh, M.Si.',
                            },
                            progress: {
                                total: 18,
                                pending: 2,
                                in_progress: 3,
                                completed: 13,
                                percent_complete: 72,
                            },
                            last_activity_at: '2026-09-29T10:10:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 30,
                                reason: null,
                            },
                        },
                    ],
                },
                {
                    reference: 'aggregate-sekda-asisten-iii',
                    recipient_position: {
                        code: 'ASISTEN-III',
                        name: 'Asisten Administrasi Umum',
                        unit_name: 'Sekretariat Daerah',
                        official_name: 'Dra. Nur Aisyah, M.Si.',
                    },
                    progress: {
                        total: 35,
                        pending: 2,
                        in_progress: 7,
                        completed: 26,
                        percent_complete: 74,
                    },
                    last_activity_at: '2026-09-30T14:35:00+08:00',
                    attention: {
                        needs_attention: false,
                        idle_hours: 4,
                        reason: null,
                    },
                    children: [
                        {
                            reference: 'aggregate-sekda-asisten-iii-umum',
                            recipient_position: {
                                code: 'KABAG_UMUM',
                                name: 'Kepala Bagian Umum',
                                unit_name: 'Bagian Umum',
                                official_name: 'Hendra Wijaya, S.Sos.',
                            },
                            progress: {
                                total: 18,
                                pending: 1,
                                in_progress: 3,
                                completed: 14,
                                percent_complete: 78,
                            },
                            last_activity_at: '2026-09-30T14:35:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 4,
                                reason: null,
                            },
                        },
                        {
                            reference: 'aggregate-sekda-asisten-iii-organisasi',
                            recipient_position: {
                                code: 'KABAG_ORGANISASI',
                                name: 'Kepala Bagian Organisasi',
                                unit_name: 'Bagian Organisasi',
                                official_name: 'Fitriani, S.IP., M.Si.',
                            },
                            progress: {
                                total: 17,
                                pending: 1,
                                in_progress: 4,
                                completed: 12,
                                percent_complete: 71,
                            },
                            last_activity_at: '2026-09-28T14:35:00+08:00',
                            attention: {
                                needs_attention: true,
                                idle_hours: 50,
                                reason: 'Satu cabang belum bergerak selama 50 jam.',
                            },
                        },
                    ],
                },
                {
                    reference: 'aggregate-sekda-asisten-ii',
                    recipient_position: {
                        code: 'ASISTEN-II',
                        name: 'Asisten Perekonomian dan Pembangunan',
                        unit_name: 'Sekretariat Daerah',
                        official_name: 'Ir. Fatmawati Yusuf, M.Si.',
                    },
                    progress: {
                        total: 30,
                        pending: 3,
                        in_progress: 6,
                        completed: 21,
                        percent_complete: 70,
                    },
                    last_activity_at: '2026-09-28T12:10:00+08:00',
                    attention: {
                        needs_attention: true,
                        idle_hours: 52,
                        reason: 'Dua cabang belum diperbarui selama 52 jam.',
                    },
                    children: [
                        {
                            reference: 'aggregate-sekda-asisten-ii-ekonomi',
                            recipient_position: {
                                code: 'KABAG_EKONOMI',
                                name: 'Kepala Bagian Ekonomi',
                                unit_name: 'Bagian Ekonomi',
                                official_name: 'Rahmat Hidayat, S.E.',
                            },
                            progress: {
                                total: 16,
                                pending: 2,
                                in_progress: 3,
                                completed: 11,
                                percent_complete: 69,
                            },
                            last_activity_at: '2026-09-28T12:10:00+08:00',
                            attention: {
                                needs_attention: true,
                                idle_hours: 52,
                                reason: 'Satu cabang belum bergerak selama 52 jam.',
                            },
                        },
                        {
                            reference: 'aggregate-sekda-asisten-ii-pembangunan',
                            recipient_position: {
                                code: 'KABAG_PEMBANGUNAN',
                                name: 'Kepala Bagian Pembangunan',
                                unit_name: 'Bagian Pembangunan',
                                official_name: 'Maya Sari, S.T., M.T.',
                            },
                            progress: {
                                total: 14,
                                pending: 1,
                                in_progress: 3,
                                completed: 10,
                                percent_complete: 71,
                            },
                            last_activity_at: '2026-09-29T15:30:00+08:00',
                            attention: {
                                needs_attention: false,
                                idle_hours: 25,
                                reason: null,
                            },
                        },
                    ],
                },
            ],
        },
    ],
};

export const previewPeriodicReportOrganizationGraph: PeriodicReportOrganizationGraph =
    {
        generated_at: previewPeriodicReportOrganizationGraphSeed.generated_at,
        executives:
            previewPeriodicReportOrganizationGraphSeed.executives.map(
                presentAggregateNode,
            ),
    };

import type {
    LetterResponseAssistantBranch,
    LetterResponseDocumentSeries,
    LetterResponseDocumentVersion,
    LetterResponseDossier,
    LetterResponseListItem,
} from '@/types';

const version = (
    id: string,
    number: number,
    filename: string,
    uploadedBy: string,
    uploadedAt: string,
    note: string | null = null,
): LetterResponseDocumentVersion => ({
    public_id: id,
    version_number: number,
    original_filename: filename,
    mime_type: 'application/pdf',
    size_bytes: 1_842_176 + number * 53_120,
    sha256_fingerprint: `a91f3c8d${id.slice(-8)}${number}bb2e`,
    uploaded_by: uploadedBy,
    uploaded_at: uploadedAt,
    revision_note: note,
    links: {
        preview: '#preview-dokumen',
        download: '#download-dokumen',
    },
});

const series = (
    publicId: string,
    kind: LetterResponseDocumentSeries['kind'],
    title: string,
    versions: LetterResponseDocumentVersion[],
    overrides: Partial<LetterResponseDocumentSeries> = {},
): LetterResponseDocumentSeries => ({
    public_id: publicId,
    kind,
    title,
    status: 'READY',
    current_version: versions.at(-1) ?? null,
    versions,
    can_upload: false,
    can_return: false,
    can_select: false,
    open_revision_reason: null,
    ...overrides,
});

const pembangunanMaterial = series(
    'material-pembangunan-01',
    'TECHNICAL_MATERIAL',
    'Bahan teknis Bagian Pembangunan',
    [
        version(
            'material-pembangunan-v1',
            1,
            'Telaah_teknis_pembangunan.pdf',
            'Rizal Hidayat',
            '2026-09-03T08:15:00+08:00',
            'Ringkasan teknis dan rekomendasi awal.',
        ),
    ],
);

const asetMaterial = series(
    'material-aset-01',
    'TECHNICAL_MATERIAL',
    'Bahan teknis Bagian Aset',
    [
        version(
            'material-aset-v1',
            1,
            'Catatan_aset_daerah.pdf',
            'Maya Lestari',
            '2026-09-04T10:40:00+08:00',
        ),
        version(
            'material-aset-v2',
            2,
            'Catatan_aset_daerah_revisi.pdf',
            'Maya Lestari',
            '2026-09-05T09:10:00+08:00',
            'Menambahkan data inventaris terbaru.',
        ),
    ],
);

const assistantProposal = series(
    'proposal-asisten-01',
    'ASSISTANT_PROPOSAL',
    'Usulan balasan Asisten II',
    [
        version(
            'proposal-asisten-v1',
            1,
            'Usulan_balasan_asisten_II.pdf',
            'Ir. Fatmawati Yusuf, M.Si.',
            '2026-09-05T11:20:00+08:00',
        ),
        version(
            'proposal-asisten-v2',
            2,
            'Usulan_balasan_asisten_II_revisi.pdf',
            'Ir. Fatmawati Yusuf, M.Si.',
            '2026-09-05T13:05:00+08:00',
            'Menyesuaikan pilihan redaksi sesuai bahan teknis Bagian Aset.',
        ),
    ],
    {
        can_select: true,
    },
);

const assistantI: LetterResponseAssistantBranch = {
    public_id: 'assistant-01',
    position_name: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
    unit_name: 'Sekretariat Daerah',
    official_name: 'Drs. Abdul Malik, M.Si.',
    status: 'COMPLETED',
    forwarded_at: '2026-09-02T14:20:00+08:00',
    child_progress: { total: 2, completed: 2, percent: 100 },
    children: [
        {
            public_id: 'section-umum-01',
            position_name: 'Kepala Bagian Umum',
            unit_name: 'Bagian Umum',
            official_name: 'Siti Rahmawati, S.STP.',
            status: 'COMPLETED',
            received_at: '2026-09-02T15:00:00+08:00',
            started_at: '2026-09-03T08:00:00+08:00',
            completed_at: '2026-09-04T15:30:00+08:00',
            completion_note:
                'Administrasi penerimaan aset telah diverifikasi dan dapat dilanjutkan ke telaah teknis.',
            material: null,
            can_upload_material: false,
        },
        {
            public_id: 'section-pembangunan-01',
            position_name: 'Kepala Bagian Pembangunan',
            unit_name: 'Bagian Pembangunan',
            official_name: 'Rizal Hidayat, S.STP.',
            status: 'COMPLETED',
            received_at: '2026-09-02T15:02:00+08:00',
            started_at: '2026-09-03T08:30:00+08:00',
            completed_at: '2026-09-05T10:00:00+08:00',
            completion_note:
                'Usulan penataan aset layak dilaksanakan bertahap dengan pendataan kondisi awal.',
            material: pembangunanMaterial,
            can_upload_material: false,
        },
    ],
    proposal: null,
    can_submit_proposal: false,
    can_return_material: false,
};

const assistantII: LetterResponseAssistantBranch = {
    public_id: 'assistant-02',
    position_name: 'Asisten Perekonomian dan Pembangunan',
    unit_name: 'Sekretariat Daerah',
    official_name: 'Ir. Fatmawati Yusuf, M.Si.',
    status: 'COMPLETED',
    forwarded_at: '2026-09-02T15:10:00+08:00',
    child_progress: { total: 1, completed: 1, percent: 100 },
    children: [
        {
            public_id: 'section-aset-01',
            position_name: 'Kepala Bagian Aset',
            unit_name: 'Bagian Pengelolaan Aset',
            official_name: 'Maya Lestari, S.E.',
            status: 'COMPLETED',
            received_at: '2026-09-02T15:30:00+08:00',
            started_at: '2026-09-03T09:00:00+08:00',
            completed_at: '2026-09-05T09:30:00+08:00',
            completion_note:
                'Data inventaris aset telah dilengkapi; diperlukan sinkronisasi daftar prioritas dengan Bagian Pembangunan.',
            material: asetMaterial,
            can_upload_material: false,
        },
    ],
    proposal: assistantProposal,
    can_submit_proposal: false,
    can_return_material: false,
};

export const previewLetterResponseDossier: LetterResponseDossier = {
    public_id: 'response-dossier-2026-001',
    status: 'OPEN',
    opened_at: '2026-09-05T13:20:00+08:00',
    finalized_at: null,
    letter: {
        reference: 'INCOMING-2026-00042',
        agenda_number: '400.10/42/UM/2026',
        subject: 'Permohonan penataan dan pemanfaatan aset daerah',
        sender_organization_name: 'Forum Kelurahan Betoambari',
        source: 'ONLINE',
        status: 'COMPLETED',
        received_at: '2026-09-01T09:12:00+08:00',
    },
    executive: {
        position_name: 'Sekretaris Daerah',
        official_name: 'Drs. La Ode Ahmad, M.Si.',
        role: 'EXECUTIVE',
    },
    assistants: [assistantI, assistantII],
    progress: { total: 3, completed: 3, percent: 100 },
    mandates: [
        {
            public_id: 'mandate-01',
            subject: 'Balasan permohonan penataan aset daerah',
            version_number: 1,
            signatory_name: 'Drs. La Ode Ahmad, M.Si.',
            signatory_position: 'Sekretaris Daerah',
            status: 'AUTHORIZED',
            created_at: '2026-09-05T14:00:00+08:00',
        },
    ],
    consolidation: null,
    eligible_signatories: [
        {
            code: 'SEKRETARIS_DAERAH',
            name: 'Sekretaris Daerah',
            official_name: 'Drs. La Ode Ahmad, M.Si.',
        },
        {
            code: 'ASISTEN_II',
            name: 'Asisten Perekonomian dan Pembangunan',
            official_name: 'Ir. Fatmawati Yusuf, M.Si.',
        },
    ],
    viewer: {
        role: 'EXECUTIVE',
        display_name: 'Sekda',
        can_view: true,
        can_contribute: true,
        can_review: true,
        can_authorize: true,
    },
    links: {
        index: '/back-office/previews/letter-responses',
        finalize: '#finalize-response-plan',
        consolidation_store: '#upload-consolidation',
        mandate_store: '#create-mandate',
    },
};

const makeListItem = (
    dossier: LetterResponseDossier,
    updatedAt: string,
): LetterResponseListItem => ({
    public_id: dossier.public_id,
    status: dossier.status,
    letter: dossier.letter,
    progress: dossier.progress,
    mandates: dossier.mandates,
    assistants_count: dossier.assistants.length,
    updated_at: updatedAt,
    links: {
        detail: `/back-office/previews/letter-responses/${dossier.public_id}`,
    },
});

export const previewLetterResponseDossiers: LetterResponseListItem[] = [
    makeListItem(previewLetterResponseDossier, '2026-09-05T14:00:00+08:00'),
    {
        ...makeListItem(previewLetterResponseDossier, '2026-09-04T16:10:00+08:00'),
        public_id: 'response-dossier-2026-00037',
        status: 'FINALIZED',
        letter: {
            ...previewLetterResponseDossier.letter,
            reference: 'INCOMING-2026-00037',
            agenda_number: '500.12/37/UM/2026',
            subject: 'Permohonan fasilitasi kegiatan ekonomi kreatif',
            source: 'MANUAL',
        },
        progress: { total: 2, completed: 2, percent: 100 },
        assistants_count: 1,
        mandates: [
            {
                ...previewLetterResponseDossier.mandates[0],
                public_id: 'mandate-archive-01',
                subject: 'Balasan fasilitasi ekonomi kreatif',
            },
        ],
        links: {
            detail: '/back-office/previews/letter-responses/response-dossier-2026-00037',
        },
    },
];

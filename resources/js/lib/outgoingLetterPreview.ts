import type {
    OutgoingLetterDetail,
    OutgoingLetterFilters,
    OutgoingLetterListItem,
    OutgoingLetterSummary,
    PublicResponseTracker,
    LetterSubmission,
    SekdaApprovalDetail,
} from '@/types';

export const previewOutgoingFilters: OutgoingLetterFilters = {
    search: '',
    status: '',
    source: '',
    origin: '',
    year: '2026',
};

export const previewOutgoingSummary: OutgoingLetterSummary = {
    total: 18,
    awaiting_number: 3,
    awaiting_verification: 4,
    ready_for_delivery: 2,
    delivered_this_month: 9,
};

export const previewStandaloneNumberingQueue = [
    {
        public_id: '01KSTANDALONENUMBER000000001',
        draft_public_id: '01KSTANDALONEDRAFT000000001',
        subject: 'Undangan koordinasi persiapan Festival Pesona Buton',
        recipient_name: 'Ketua Panitia Festival Pesona Buton',
        recipient_organization: 'Panitia Festival Pesona Buton',
        originating_unit_name: 'Bagian Perekonomian dan Pembangunan',
        current_version_number: 2,
        sha256_fingerprint:
            'e89f66a7c9452af4ab6ce14ec17d353c2a9d4e7e34e8513b7b0c2375d216a6b5',
        approved_at: '2026-09-08T10:14:00+08:00',
        assign_number_url: '#standalone-assign-number',
    },
];

export const previewSekdaApprovalQueue = [
    {
        public_id: '01KSTANDALONEOUTGOING00000001',
        subject: 'Undangan koordinasi persiapan Festival Pesona Buton',
        outgoing_number: '005/1201/SETDA/2026',
        letter_date: '2026-09-09',
        originating_unit_name: 'Bagian Perekonomian dan Pembangunan',
        recipient_name: 'Ketua Panitia Festival Pesona Buton',
        sha256_fingerprint:
            'e89f66a7c9452af4ab6ce14ec17d353c2a9d4e7e34e8513b7b0c2375d216a6b5',
        approval_url:
            '/back-office/previews/outgoing-letters/approvals/01KSTANDALONEOUTGOING00000001',
    },
];

export const previewOutgoingLetters: OutgoingLetterListItem[] = [
    {
        public_id: '01KOUTGOING000000000000001',
        subject: 'Balasan permohonan penataan aset daerah',
        status: 'AUTHORIZED',
        origin: 'RESPONSE',
        source: 'ONLINE',
        incoming_agenda_number: '500.12/41/UM/2026',
        sender_organization_name: 'Forum Masyarakat Kelurahan Wameo',
        originating_unit_name: null,
        outgoing_number: null,
        letter_date: null,
        signatory_position: 'Sekretaris Daerah',
        signatory_name: 'Drs. La Ode Ahmad, M.Si.',
        authorized_at: '2026-09-05T14:00:00+08:00',
        updated_at: '2026-09-05T14:00:00+08:00',
        next_action_label: 'Berikan nomor surat',
        links: {
            detail: '/back-office/previews/outgoing-letters/01KOUTGOING000000000000001',
            sekda_approval: null,
        },
    },
    {
        public_id: '01KOUTGOING000000000000002',
        subject: 'Jawaban fasilitasi kegiatan ekonomi kreatif',
        status: 'NUMBER_ASSIGNED',
        origin: 'RESPONSE',
        source: 'MANUAL',
        incoming_agenda_number: '500.12/37/UM/2026',
        sender_organization_name: 'Komunitas Kreatif Baubau',
        originating_unit_name: null,
        outgoing_number: '500.12/1189/SETDA/2026',
        letter_date: '2026-09-05',
        signatory_position: 'Asisten Perekonomian dan Pembangunan',
        signatory_name: 'Ir. Fatmawati Yusuf, M.Si.',
        authorized_at: '2026-09-04T10:15:00+08:00',
        updated_at: '2026-09-05T09:12:00+08:00',
        next_action_label: 'Unggah PDF bertanda tangan',
        links: {
            detail: '/back-office/previews/outgoing-letters/01KOUTGOING000000000000002',
            sekda_approval: null,
        },
    },
    {
        public_id: '01KOUTGOING000000000000003',
        subject: 'Tanggapan atas permohonan audiensi kelembagaan',
        status: 'SIGNED_DOCUMENT_UPLOADED',
        origin: 'RESPONSE',
        source: 'ONLINE',
        incoming_agenda_number: '005/52/UM/2026',
        sender_organization_name: 'Lembaga Adat Wolio',
        originating_unit_name: null,
        outgoing_number: '005/1193/SETDA/2026',
        letter_date: '2026-09-05',
        signatory_position: 'Sekretaris Daerah',
        signatory_name: 'Drs. La Ode Ahmad, M.Si.',
        authorized_at: '2026-09-04T15:20:00+08:00',
        updated_at: '2026-09-05T13:45:00+08:00',
        next_action_label: 'Verifikasi administrasi',
        links: {
            detail: '/back-office/previews/outgoing-letters/01KOUTGOING000000000000003',
            sekda_approval: null,
        },
    },
    {
        public_id: '01KOUTGOING000000000000004',
        subject: 'Pemberitahuan hasil koordinasi penataan kawasan',
        status: 'ADMIN_VERIFIED',
        origin: 'RESPONSE',
        source: 'MANUAL',
        incoming_agenda_number: '600.1/29/UM/2026',
        sender_organization_name: 'Kelompok Warga Bataraguru',
        originating_unit_name: null,
        outgoing_number: '600.1/1195/SETDA/2026',
        letter_date: '2026-09-06',
        signatory_position: 'Asisten Administrasi Umum',
        signatory_name: 'Dra. Wa Ode Nurhayati, M.Si.',
        authorized_at: '2026-09-05T08:10:00+08:00',
        updated_at: '2026-09-06T09:20:00+08:00',
        next_action_label: 'Catat pengiriman',
        links: {
            detail: '/back-office/previews/outgoing-letters/01KOUTGOING000000000000004',
            sekda_approval: null,
        },
    },
    {
        public_id: '01KOUTGOING000000000000005',
        subject: 'Balasan permohonan data program pembangunan',
        status: 'DELIVERED',
        origin: 'RESPONSE',
        source: 'ONLINE',
        incoming_agenda_number: '050/33/UM/2026',
        sender_organization_name: 'Universitas Dayanu Ikhsanuddin',
        originating_unit_name: null,
        outgoing_number: '050/1178/SETDA/2026',
        letter_date: '2026-09-02',
        signatory_position: 'Sekretaris Daerah',
        signatory_name: 'Drs. La Ode Ahmad, M.Si.',
        authorized_at: '2026-09-01T09:30:00+08:00',
        updated_at: '2026-09-03T10:42:00+08:00',
        next_action_label: null,
        links: {
            detail: '/back-office/previews/outgoing-letters/01KOUTGOING000000000000005',
            sekda_approval: null,
        },
    },
];

export const previewOutgoingLetterDetail: OutgoingLetterDetail = {
    public_id: '01KOUTGOING000000000000003',
    subject: 'Tanggapan atas permohonan audiensi kelembagaan',
    status: 'SIGNED_DOCUMENT_UPLOADED',
    origin: 'RESPONSE',
    source: 'ONLINE',
    incoming_letter: {
        reference: 'INCOMING-2026-00052',
        agenda_number: '005/52/UM/2026',
        subject: 'Permohonan audiensi penguatan kelembagaan adat',
        sender_organization_name: 'Lembaga Adat Wolio',
        received_at: '2026-08-28T09:20:00+08:00',
        dossier_url:
            '/back-office/previews/letter-responses/response-dossier-2026-001',
    },
    standalone_draft: null,
    mandate: {
        source_document_title: 'Konsolidasi akhir Sekretaris Daerah',
        source_version_number: 2,
        authorized_by: 'Drs. La Ode Ahmad, M.Si.',
        authorized_position: 'Sekretaris Daerah',
        authorized_at: '2026-09-04T15:20:00+08:00',
    },
    signatory: {
        position_name: 'Sekretaris Daerah',
        official_name: 'Drs. La Ode Ahmad, M.Si.',
    },
    numbering: {
        outgoing_number: '005/1193/SETDA/2026',
        agenda_year: 2026,
        letter_date: '2026-09-05',
        numbered_by: 'Nur Aisyah, S.A.P.',
        numbered_at: '2026-09-05T09:08:00+08:00',
    },
    documents: [
        {
            public_id: '01KOUTDOC00000000000000001',
            version_number: 1,
            original_filename: 'balasan-audiensi-bertandatangan.pdf',
            mime_type: 'application/pdf',
            size_bytes: 1_842_320,
            sha256_fingerprint:
                'd3219061f4d68ce0ce1888a1dbe593376dad2248ef12e978711f683f27a16b42',
            uploaded_by: 'Nur Aisyah, S.A.P.',
            uploaded_at: '2026-09-05T13:45:00+08:00',
            upload_note:
                'Dokumen telah diberi nomor dan ditandatangani Sekretaris Daerah.',
            review_status: 'PENDING',
            review_note: null,
            links: {
                preview: '#preview-final-pdf',
                download: '#download-final-pdf',
            },
        },
    ],
    verification: {
        verified_by: null,
        verified_position: null,
        verified_at: null,
        note: null,
    },
    delivery: {
        method: null,
        recipient_email: 'ilham@example.test',
        recipient_name: null,
        delivered_by: null,
        delivered_at: null,
        tracking_number: null,
        note: null,
        email: {
            status: 'NOT_SENT',
            sent_at: null,
            expires_at: null,
            download_url_status: 'Belum dibuat',
            resend_url: '#resend-email-link',
            revoke_url: '#revoke-email-link',
        },
    },
    internal_copies: [
        {
            name: 'Sekretaris Daerah',
            position: 'Sekretaris Daerah',
            notified_at: null,
            acknowledged: false,
        },
        {
            name: 'Kabag Umum',
            position: 'Kepala Bagian Umum',
            notified_at: null,
            acknowledged: false,
        },
    ],
    withdrawal: null,
    history: [
        {
            key: 'authorized',
            label: 'Mandat diterbitkan',
            description:
                'Substansi balasan disetujui dan masuk antrean administrasi surat keluar.',
            actor_name: 'Drs. La Ode Ahmad, M.Si.',
            actor_position: 'Sekretaris Daerah',
            occurred_at: '2026-09-04T15:20:00+08:00',
            state: 'DONE',
        },
        {
            key: 'numbered',
            label: 'Nomor resmi diberikan',
            description: 'Nomor 005/1193/SETDA/2026 dicatat untuk tahun 2026.',
            actor_name: 'Nur Aisyah, S.A.P.',
            actor_position: 'Petugas Surat Bagian Umum',
            occurred_at: '2026-09-05T09:08:00+08:00',
            state: 'DONE',
        },
        {
            key: 'uploaded',
            label: 'PDF bertanda tangan diunggah',
            description:
                'Versi pertama dokumen final menunggu pemeriksaan administratif.',
            actor_name: 'Nur Aisyah, S.A.P.',
            actor_position: 'Petugas Surat Bagian Umum',
            occurred_at: '2026-09-05T13:45:00+08:00',
            state: 'CURRENT',
        },
    ],
    capabilities: {
        can_assign_number: false,
        can_upload_signed_document: true,
        can_verify: true,
        can_request_document_revision: true,
        can_deliver: false,
        can_withdraw: false,
        can_select_sekda_approval: false,
        can_upload_manual_scan: false,
        can_review_manual_scan: false,
        can_return_for_revision: false,
    },
    routes: {
        index: '/back-office/previews/outgoing-letters',
        assign_number: '#assign-number',
        upload_signed_document: '#upload-signed-document',
        verify: '#verify-document',
        request_document_revision: '#return-document',
        deliver: '#deliver-letter',
        withdraw: '#withdraw-mandate',
        sekda_approval: null,
        approve_qr: null,
        choose_manual_signature: null,
        upload_manual_scan: null,
        review_manual_scan: null,
        return_for_revision: null,
        create_correction: null,
        resend_delivery_email: null,
        revoke_delivery_email: null,
    },
};

export function previewOutgoingLetterDetailFor(
    publicId: string,
): OutgoingLetterDetail {
    const detail = structuredClone(previewOutgoingLetterDetail);
    const listItem = previewOutgoingLetters.find(
        (letter) => letter.public_id === publicId,
    );

    if (!listItem) {
        return detail;
    }

    detail.public_id = listItem.public_id;
    detail.subject = listItem.subject;
    detail.status = listItem.status;
    detail.source = listItem.source;

    if (detail.incoming_letter) {
        detail.incoming_letter.agenda_number =
            listItem.incoming_agenda_number ?? '';
        detail.incoming_letter.sender_organization_name =
            listItem.sender_organization_name ?? '';
    }

    detail.numbering.outgoing_number = listItem.outgoing_number;
    detail.numbering.letter_date = listItem.letter_date;
    detail.signatory.position_name = listItem.signatory_position;
    detail.signatory.official_name = listItem.signatory_name;
    detail.capabilities = {
        can_assign_number: false,
        can_upload_signed_document: false,
        can_verify: false,
        can_request_document_revision: false,
        can_deliver: false,
        can_withdraw: false,
        can_select_sekda_approval: false,
        can_upload_manual_scan: false,
        can_review_manual_scan: false,
        can_return_for_revision: false,
    };

    if (listItem.status === 'AUTHORIZED') {
        detail.numbering = {
            outgoing_number: null,
            agenda_year: null,
            letter_date: null,
            numbered_by: null,
            numbered_at: null,
        };
        detail.documents = [];
        detail.history = detail.history.slice(0, 1);
        detail.capabilities.can_assign_number = true;
        detail.capabilities.can_withdraw = true;
    } else if (listItem.status === 'NUMBER_ASSIGNED') {
        detail.documents = [];
        detail.history = detail.history.slice(0, 2);
        detail.capabilities.can_upload_signed_document = true;
    } else if (listItem.status === 'SIGNED_DOCUMENT_UPLOADED') {
        detail.capabilities.can_verify = true;
        detail.capabilities.can_request_document_revision = true;
    } else if (listItem.status === 'ADMIN_VERIFIED') {
        detail.documents[0]!.review_status = 'VERIFIED';
        detail.verification = {
            verified_by: 'H. Muhammad Amin, S.Sos.',
            verified_position: 'Kepala Bagian Umum',
            verified_at: '2026-09-06T09:20:00+08:00',
            note: 'Nomor, tanggal, penandatangan, dan dokumen telah sesuai.',
        };
        detail.capabilities.can_deliver = true;
    } else if (listItem.status === 'DELIVERED') {
        detail.documents[0]!.review_status = 'VERIFIED';
        detail.verification = {
            verified_by: 'H. Muhammad Amin, S.Sos.',
            verified_position: 'Kepala Bagian Umum',
            verified_at: '2026-09-03T09:15:00+08:00',
            note: 'Dokumen final sesuai register.',
        };
        detail.delivery = {
            method: 'EMAIL',
            recipient_email: 'ilham@example.test',
            recipient_name: 'Pemilik pengajuan online',
            delivered_by: 'Nur Aisyah, S.A.P.',
            delivered_at: '2026-09-03T10:42:00+08:00',
            tracking_number: null,
            note: 'Tautan unduh aman dikirim melalui email tanpa lampiran PDF.',
            email: {
                status: 'SENT',
                sent_at: '2026-09-03T10:42:00+08:00',
                expires_at: '2026-09-10T10:42:00+08:00',
                download_url_status: 'Tautan aktif',
                resend_url: '#resend-email-link',
                revoke_url: '#revoke-email-link',
            },
        };
        detail.capabilities.can_create_correction = true;
        detail.capabilities.can_resend_delivery_email = true;
        detail.capabilities.can_revoke_delivery_email = true;
        detail.routes.create_correction = '#create-correction';
        detail.routes.resend_delivery_email = '#resend-email-link';
        detail.routes.revoke_delivery_email = '#revoke-email-link';
    }

    return detail;
}

export const previewPublicResponseTracker: PublicResponseTracker = {
    status: 'RESPONSE_AVAILABLE',
    updated_at: '2026-09-03T10:42:00+08:00',
    responses: [
        {
            public_id: '01KPUBLICRESPONSE0000000001',
            outgoing_number: '050/1178/SETDA/2026',
            letter_date: '2026-09-02',
            subject: 'Balasan permohonan data program pembangunan',
            signatory_position: 'Sekretaris Daerah',
            delivered_at: '2026-09-03T10:42:00+08:00',
            preview_url: '#preview-official-response',
            download_url: '#download-official-response',
        },
    ],
};

export const previewSekdaApproval: SekdaApprovalDetail = {
    public_id: '01KSTANDALONEOUTGOING00000001',
    subject: 'Undangan koordinasi persiapan Festival Pesona Buton',
    status: 'SEKDA_REVIEW',
    outgoing_number: '005/1201/SETDA/2026',
    letter_date: '2026-09-09',
    originating_unit_name: 'Bagian Perekonomian dan Pembangunan',
    recipient: {
        name: 'Ketua Panitia Festival Pesona Buton',
        organization: 'Panitia Festival Pesona Buton',
        position: 'Ketua Panitia',
    },
    copy_recipients: ['Asisten Perekonomian dan Pembangunan', 'Kabag Umum'],
    current_document: {
        version_number: 2,
        sha256_fingerprint:
            'e89f66a7c9452af4ab6ce14ec17d353c2a9d4e7e34e8513b7b0c2375d216a6b5',
        preview_url: '#preview-standalone-numbered-pdf',
        download_url: '#download-standalone-numbered-pdf',
    },
    qr_placement: {
        page_label: 'Halaman terakhir',
        x_ratio: 0.67,
        y_ratio: 0.72,
        width_ratio: 0.2,
        height_ratio: 0.14,
    },
    manual_scan: null,
    review: null,
    capabilities: {
        can_approve_qr: true,
        can_choose_manual_signature: true,
        can_upload_manual_scan: false,
        can_review_manual_scan: false,
        can_return_for_revision: true,
    },
    routes: {
        index: '/back-office/previews/outgoing-letters',
        approve_qr: '#approve-with-qr',
        choose_manual_signature: '#choose-manual-signature',
        upload_manual_scan: '#upload-manual-scan',
        review_manual_scan: '#review-manual-scan',
        return_for_revision: '#return-for-revision',
    },
};

export const previewPublicSubmission: LetterSubmission = {
    public_id: '01KPUBLICSUBMISSION000000001',
    source: 'ONLINE',
    status: 'REGISTERED',
    sender_organization_name: 'Universitas Dayanu Ikhsanuddin',
    contact_name: 'Muhammad Ilham',
    contact_email: 'ilham@example.test',
    contact_phone: '081234567890',
    external_letter_number: '021/UND/VIII/2026',
    external_letter_date: '2026-08-27',
    subject: 'Permohonan data program pembangunan daerah',
    summary: 'Permohonan data untuk kebutuhan penelitian kelembagaan.',
    submitted_at: '2026-08-27T10:15:00+08:00',
    created_at: '2026-08-27T09:50:00+08:00',
    updated_at: '2026-09-03T10:42:00+08:00',
    revision_note: null,
    rejection_note: null,
    document: {
        original_filename: 'permohonan-data-pembangunan.pdf',
        mime_type: 'application/pdf',
        size_bytes: 842_300,
        uploaded_at: '2026-08-27T09:50:00+08:00',
    },
    capabilities: {
        can_update: false,
        can_replace_document: false,
        can_submit: false,
        can_delete: false,
        can_download_document: true,
    },
};

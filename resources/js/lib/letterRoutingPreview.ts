import type {
    ExecutiveInboxItem,
    ExecutiveInboxSummary,
    ExecutivePositionOption,
    LetterRoutingItem,
    LetterRoutingSummary,
} from '@/types';

export const previewExecutivePositions: ExecutivePositionOption[] = [
    {
        id: 11,
        code: 'WALIKOTA',
        name: 'Wali Kota',
        holder_name: 'Dr. H. Ahmad Darmawan, S.E., M.Si.',
        is_available: true,
    },
    {
        id: 12,
        code: 'SEKDA',
        name: 'Sekretaris Daerah',
        holder_name: 'Ir. Nurhayati Rahman, M.Si.',
        is_available: true,
    },
];

export const previewLetterRoutingItems: LetterRoutingItem[] = [
    {
        id: 101,
        agenda_number: '0189/UMUM/VIII/2026',
        agenda_year: 2026,
        subject: 'Permohonan audiensi terkait penataan kawasan pesisir',
        sender_organization_name: 'Forum Masyarakat Pesisir Kota',
        external_letter_number: '042/FMPK/VIII/2026',
        received_at: '2026-08-31T08:12:00+08:00',
        status: 'REGISTERED',
        current_document: {
            version_number: 2,
            original_filename: 'permohonan_audiensi_pesisir_revisi.pdf',
            mime_type: 'application/pdf',
            size_bytes: 2845610,
            sha256: 'a1b2c3d4e5f67890123456789abcdef0123456789abcdef0123456789abcdef0',
            recorded_at: '2026-08-31T08:19:00+08:00',
            preview_url: '#preview-document',
            download_url: '#download-document',
        },
        current_route: null,
        links: {
            show: '/back-office/previews/letter-routing/letters/101',
        },
    },
    {
        id: 102,
        agenda_number: '0188/UMUM/VIII/2026',
        agenda_year: 2026,
        subject: 'Undangan rapat koordinasi pengendalian inflasi daerah',
        sender_organization_name: 'Sekretariat Tim Pengendalian Inflasi Daerah',
        external_letter_number: '500.2/117/TPID/2026',
        received_at: '2026-08-30T15:46:00+08:00',
        status: 'REGISTERED',
        current_document: {
            version_number: 1,
            original_filename: 'undangan_rakor_tpid_agustus.pdf',
            mime_type: 'application/pdf',
            size_bytes: 1364220,
            sha256: 'b72c6c7fdf49a0cb947aaf8ce10e1300f88b809e49ae8a47466fe968aed70cf1',
            recorded_at: '2026-08-30T15:51:00+08:00',
            preview_url: '#preview-document',
            download_url: '#download-document',
        },
        current_route: null,
        links: {
            show: '/back-office/previews/letter-routing/letters/102',
        },
    },
    {
        id: 103,
        agenda_number: '0187/UMUM/VIII/2026',
        agenda_year: 2026,
        subject: 'Penyampaian hasil evaluasi pelayanan publik semester I',
        sender_organization_name: 'Ombudsman Perwakilan Provinsi',
        external_letter_number: 'B/221/LM.01/VIII/2026',
        received_at: '2026-08-30T10:20:00+08:00',
        status: 'ROUTED',
        current_document: {
            version_number: 1,
            original_filename: 'hasil_evaluasi_pelayanan_publik.pdf',
            mime_type: 'application/pdf',
            size_bytes: 4215600,
            sha256: '4cbd9fc443233e6c63c7c350e6eb01df80830ae1c9325bb842b20522554e28f8',
            recorded_at: '2026-08-30T10:27:00+08:00',
            preview_url: '#preview-document',
            download_url: '#download-document',
        },
        current_route: {
            status: 'PENDING',
            target_position: previewExecutivePositions[0],
            routed_by: {
                name: 'La Ode Rahmat Hidayat',
                position: 'Kepala Bagian Umum',
                unit: 'Bagian Umum',
            },
            routed_at: '2026-08-30T11:05:00+08:00',
        },
        links: {
            show: '/back-office/previews/letter-routing/letters/103',
        },
    },
    {
        id: 104,
        agenda_number: '0186/UMUM/VIII/2026',
        agenda_year: 2026,
        subject: 'Rekomendasi percepatan tindak lanjut hasil pemeriksaan',
        sender_organization_name: 'Inspektorat Kota',
        external_letter_number: '700.1.2/88/ITK/2026',
        received_at: '2026-08-29T09:15:00+08:00',
        status: 'ROUTED',
        current_document: {
            version_number: 3,
            original_filename: 'rekomendasi_tindak_lanjut_final.pdf',
            mime_type: 'application/pdf',
            size_bytes: 5167080,
            sha256: '9185fd272012226541cc41ff31131e669e134c2db4620876f58f80a4d99b4e5f',
            recorded_at: '2026-08-29T09:31:00+08:00',
            preview_url: '#preview-document',
            download_url: '#download-document',
        },
        current_route: {
            status: 'PENDING',
            target_position: previewExecutivePositions[1],
            routed_by: {
                name: 'La Ode Rahmat Hidayat',
                position: 'Kepala Bagian Umum',
                unit: 'Bagian Umum',
            },
            routed_at: '2026-08-29T10:02:00+08:00',
        },
        links: {
            show: '/back-office/previews/letter-routing/letters/104',
        },
    },
];

export const previewLetterRoutingSummary: LetterRoutingSummary = {
    awaiting_route: 12,
    pending_executive: 7,
    routed_today: 4,
};

export const previewExecutiveInboxItems: ExecutiveInboxItem[] = [
    {
        route_id: 503,
        letter: previewLetterRoutingItems[2],
        received_in_inbox_at: '2026-08-30T11:05:00+08:00',
        links: {
            show: '/back-office/previews/executive-inbox/routes/503',
        },
    },
];

export const previewExecutiveInboxSummary: ExecutiveInboxSummary = {
    pending: 7,
    received_today: 3,
};

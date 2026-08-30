import { approvalRoutes } from '@/lib/intakeApprovalPresentation';
import type {
    ApprovalSubmission,
    ApprovalSummary,
    SenderOrganizationOption,
} from '@/types';

const checklist = [
    {
        id: 'sender',
        label: 'Identitas pengirim dapat diverifikasi',
        description: 'Nama instansi dan kontak pengirim tersedia.',
        checked: true,
    },
    {
        id: 'letter-metadata',
        label: 'Data surat konsisten',
        description: 'Nomor, tanggal, perihal, dan ringkasan saling sesuai.',
        checked: true,
    },
    {
        id: 'document',
        label: 'Dokumen PDF terbaca dan lengkap',
        description: 'Lampiran dapat dibuka dan tidak terpotong.',
        checked: true,
    },
    {
        id: 'scope',
        label: 'Surat termasuk jalur pimpinan',
        description: 'Surat layak diproses melalui alur pimpinan.',
        checked: true,
    },
];

function makeSubmission(
    overrides: Partial<ApprovalSubmission> = {},
): ApprovalSubmission {
    const publicId = overrides.public_id ?? '01K3QW4N8X6M2H7R9T5V0C3B1A';

    return {
        public_id: publicId,
        source: 'ONLINE',
        status: 'READY_FOR_APPROVAL',
        sender_organization_name: 'Forum Kerukunan Warga Kota',
        contact_name: 'Rahmawati Yusuf',
        contact_email: 'rahmawati@example.test',
        contact_phone: '0812 3456 7890',
        external_letter_number: '014/FKWK/VIII/2026',
        external_letter_date: '2026-08-26',
        subject: 'Permohonan audiensi mengenai layanan kependudukan',
        summary:
            'Permohonan jadwal audiensi untuk menyampaikan masukan warga mengenai peningkatan layanan administrasi kependudukan.',
        submitted_at: '2026-08-28T01:35:00.000Z',
        document: {
            original_filename: 'permohonan-audiensi-fkwk.pdf',
            mime_type: 'application/pdf',
            size_bytes: 2483200,
            sha256: 'e6bc97d1256ba2110ab49160ad5a21d31b500f05b7a518044f17a7a4bcaef870',
            uploaded_at: '2026-08-28T01:31:00.000Z',
        },
        screening_review: {
            checklist: checklist.map((item) => ({ ...item })),
            note: 'Seluruh dokumen lengkap. Substansi surat ditujukan kepada pimpinan dan siap mendapatkan keputusan administratif.',
            reviewed_by: 'Staf Administrasi Surat',
            reviewed_at: '2026-08-28T03:12:00.000Z',
        },
        latest_decision: null,
        registration: null,
        timeline: [
            {
                id: 'created',
                title: 'Pengajuan surat dibuat',
                description: 'Data awal disimpan melalui portal publik.',
                occurred_at: '2026-08-28T01:20:00.000Z',
                state: 'complete',
            },
            {
                id: 'submitted',
                title: 'Dikirim ke Bagian Umum',
                description: 'Dokumen masuk ke antrean pemeriksaan petugas.',
                occurred_at: '2026-08-28T01:35:00.000Z',
                state: 'complete',
            },
            {
                id: 'ready',
                title: 'Lolos pemeriksaan petugas',
                description: 'Menunggu keputusan Kepala Bagian Umum.',
                occurred_at: '2026-08-28T03:12:00.000Z',
                state: 'current',
            },
        ],
        capabilities: {
            can_decide: true,
            can_download_document: true,
        },
        links: {
            show: approvalRoutes.show(publicId, true),
            decision: '#',
            document_preview: '#',
            document_download: '#',
        },
        ...overrides,
    };
}

export const previewApprovalSubmissions: ApprovalSubmission[] = [
    makeSubmission(),
    makeSubmission({
        public_id: '01K3QW7P4E2A9B6D8F1G5H0J3K',
        sender_organization_name: 'Yayasan Pendidikan Bahari',
        external_letter_number: '082/YPB/VIII/2026',
        subject: 'Undangan pembukaan kegiatan literasi pesisir',
        submitted_at: '2026-08-27T06:15:00.000Z',
        screening_review: {
            checklist: checklist.map((item) => ({ ...item })),
            note: 'Undangan dan jadwal kegiatan sudah lengkap.',
            reviewed_by: 'Staf Administrasi Surat',
            reviewed_at: '2026-08-27T07:40:00.000Z',
        },
    }),
    makeSubmission({
        public_id: '01K3QW9T2N5S7U4V6X8Y1Z0A3B',
        status: 'REGISTERED',
        sender_organization_name: 'Kantor Pertanahan Kota',
        external_letter_number: '510/247/VIII/2026',
        subject: 'Koordinasi penataan aset pemerintah kota',
        submitted_at: '2026-08-25T02:10:00.000Z',
        latest_decision: {
            outcome: 'REGISTERED',
            note: 'Diregistrasikan untuk diteruskan melalui jalur pimpinan.',
            decided_by: 'Kepala Bagian Umum',
            decided_at: '2026-08-26T01:45:00.000Z',
        },
        registration: {
            agenda_number: 'AG-0187',
            agenda_year: 2026,
            sender_organization_name: 'Kantor Pertanahan Kota',
            registered_at: '2026-08-26T01:45:00.000Z',
            official_document: {
                version_number: 1,
                original_filename: 'koordinasi-penataan-aset.pdf',
                mime_type: 'application/pdf',
                size_bytes: 2483200,
                sha256: 'e6bc97d1256ba2110ab49160ad5a21d31b500f05b7a518044f17a7a4bcaef870',
                recorded_at: '2026-08-26T01:45:00.000Z',
                source: 'SUBMISSION_DOCUMENT',
            },
        },
        capabilities: {
            can_decide: false,
            can_download_document: true,
        },
    }),
    makeSubmission({
        public_id: '01K3QWBH6C9D2E5F7G8J1K4M0N',
        status: 'REJECTED',
        sender_organization_name: 'Komunitas Peduli Lingkungan',
        external_letter_number: null,
        subject: 'Permohonan penggunaan halaman kantor',
        submitted_at: '2026-08-24T05:22:00.000Z',
        latest_decision: {
            outcome: 'REJECTED',
            note: 'Permohonan tidak dapat diproses karena lokasi dan waktu kegiatan tidak berada dalam kewenangan kantor.',
            decided_by: 'Kepala Bagian Umum',
            decided_at: '2026-08-25T02:30:00.000Z',
        },
        capabilities: {
            can_decide: false,
            can_download_document: true,
        },
    }),
];

export const previewApprovalSummary: ApprovalSummary = {
    awaiting_decision: 2,
    returned_to_staff: 1,
    registered: 8,
    rejected: 2,
};

export const previewSenderOrganizations: SenderOrganizationOption[] = [
    {
        id: 1,
        name: 'Pemerintah Provinsi',
        address: 'Kompleks Perkantoran Provinsi',
        contact: null,
    },
    {
        id: 2,
        name: 'Kantor Pertanahan Kota',
        address: 'Jalan Poros Utama',
        contact: '(0401) 555010',
    },
    {
        id: 3,
        name: 'Forum Kerukunan Warga Kota',
        address: null,
        contact: '0812 3456 7890',
    },
];

export const previewApprovalDetail = previewApprovalSubmissions[0];

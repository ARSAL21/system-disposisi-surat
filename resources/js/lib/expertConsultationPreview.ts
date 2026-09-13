import type {
    ExpertAdvisorOption,
    ExpertConsultation,
} from '@/types';
import { previewLetterRoutingItems } from './letterRoutingPreview';

export const previewExpertAdvisors: ExpertAdvisorOption[] = [
    {
        id: 31,
        code: 'STAF_AHLI_PEMERINTAHAN_HUKUM',
        name: 'Staf Ahli Pemerintahan, Politik, dan Hukum',
        field: 'Pemerintahan, politik, dan hukum',
        holder_name: 'Dr. Hj. Siti Rahmawati, S.H., M.H.',
        is_available: true,
    },
    {
        id: 32,
        code: 'STAF_AHLI_EKONOMI_PEMBANGUNAN',
        name: 'Staf Ahli Ekonomi dan Pembangunan',
        field: 'Ekonomi dan pembangunan',
        holder_name: 'Ir. Yusuf Ibrahim, M.Si.',
        is_available: true,
    },
    {
        id: 33,
        code: 'STAF_AHLI_KEMASYARAKATAN_SDM',
        name: 'Staf Ahli Kemasyarakatan dan SDM',
        field: 'Kemasyarakatan dan sumber daya manusia',
        holder_name: 'Dr. Nur Aisyah, M.Si.',
        is_available: true,
    },
];

export const previewExpertConsultations: ExpertConsultation[] = [
    {
        id: 801,
        letter: previewLetterRoutingItems[2],
        advisor: previewExpertAdvisors[0],
        status: 'PENDING',
        request_note:
            'Mohon telaah aspek pemerintahan dan hukum sebelum arahan diteruskan kepada Sekda.',
        requested_at: '2026-08-30T11:20:00+08:00',
        report: null,
        links: {
            show: '/back-office/previews/expert-consultations/801',
            report: '/back-office/previews/expert-consultations/801/report',
            cancel: '/back-office/previews/expert-consultations/801/cancel',
        },
    },
    {
        id: 802,
        letter: previewLetterRoutingItems[2],
        advisor: previewExpertAdvisors[1],
        status: 'REPORTED',
        request_note:
            'Mohon berikan pertimbangan dampak ekonomi dan kebutuhan koordinasi lintas unit.',
        requested_at: '2026-08-30T11:20:00+08:00',
        report: {
            summary:
                'Permohonan berkaitan dengan penataan kawasan dan membutuhkan koordinasi lintas urusan.',
            recommendation:
                'Pertimbangkan pembahasan bersama Bagian Perekonomian dan Pembangunan setelah Sekda memberikan arahan.',
            reported_at: '2026-08-30T14:35:00+08:00',
            reported_by: {
                name: 'Ir. Yusuf Ibrahim, M.Si.',
                position: 'Staf Ahli Ekonomi dan Pembangunan',
                unit: 'Staf Ahli Wali Kota',
            },
            document: null,
        },
        links: {
            show: '/back-office/previews/expert-consultations/802',
        },
    },
];

export const previewExpertConsultationRoutes = {
    index: '/back-office/previews/expert-consultations',
    store: '/back-office/previews/executive/inbox/routes/503/expert-consultations',
    coordination: '/back-office/previews/expert-consultations/coordination',
};


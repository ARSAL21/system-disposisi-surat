import type { ApprovalDecisionOutcome } from '@/types';

const previewBase = '/back-office/previews/intake-approvals';
const productionBase = '/back-office/intake/approvals';

export const approvalRoutes = {
    index: import.meta.env.DEV ? previewBase : productionBase,
    productionIndex: productionBase,
    show: (publicId: string, preview = import.meta.env.DEV) =>
        `${preview ? previewBase : productionBase}/${encodeURIComponent(publicId)}`,
};

export function approvalDecisionLabel(
    outcome: ApprovalDecisionOutcome,
): string {
    return {
        INTERNAL_REVISION_REQUIRED: 'Dikembalikan kepada petugas',
        REJECTED: 'Ditolak secara administratif',
        REGISTERED: 'Diregistrasikan sebagai surat masuk',
    }[outcome];
}

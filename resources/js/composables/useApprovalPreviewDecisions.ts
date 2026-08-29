import { ref, type Ref } from 'vue';
import { toast } from 'vue-sonner';
import type {
    ApprovalDecision,
    ApprovalSubmission,
    RegisterApprovalPayload,
    RejectApprovalPayload,
    ReturnApprovalPayload,
    SenderOrganizationOption,
} from '@/types';

export function useApprovalPreviewDecisions(
    initialSubmission: ApprovalSubmission,
    previewMode: Ref<boolean>,
    organizations: () => SenderOrganizationOption[],
) {
    const submission = ref<ApprovalSubmission>({ ...initialSubmission });
    const returnDialogOpen = ref(false);
    const rejectDialogOpen = ref(false);
    const registerDialogOpen = ref(false);

    function applyPreviewDecision(decision: ApprovalDecision): void {
        submission.value = {
            ...submission.value,
            status: decision.outcome,
            latest_decision: decision,
            capabilities: {
                ...submission.value.capabilities,
                can_decide: false,
            },
        };
        toast.success('Keputusan diterapkan pada data pratinjau.');
    }

    function decide(
        payload: ReturnApprovalPayload | RejectApprovalPayload,
        dialog: 'return' | 'reject',
    ): void {
        if (!previewMode.value) {
            toast.info(
                'Integrasi backend akan dilakukan setelah UI disetujui.',
            );
            return;
        }

        applyPreviewDecision({
            outcome: payload.outcome,
            note: payload.note,
            decided_by: 'Kepala Bagian Umum',
            decided_at: new Date().toISOString(),
        });
        returnDialogOpen.value =
            dialog === 'return' ? false : returnDialogOpen.value;
        rejectDialogOpen.value =
            dialog === 'reject' ? false : rejectDialogOpen.value;
    }

    function register(payload: RegisterApprovalPayload): void {
        if (!previewMode.value) {
            toast.info(
                'Integrasi backend akan dilakukan setelah UI disetujui.',
            );
            return;
        }

        const senderName =
            payload.sender_organization.mode === 'new'
                ? payload.sender_organization.name
                : (organizations().find(
                      ({ id }) => id === payload.sender_organization.id,
                  )?.name ?? submission.value.sender_organization_name);
        const decidedAt = new Date().toISOString();

        applyPreviewDecision({
            outcome: 'REGISTERED',
            note: payload.note,
            decided_by: 'Kepala Bagian Umum',
            decided_at: decidedAt,
        });
        submission.value.registration = {
            agenda_number: payload.agenda_number,
            agenda_year: new Date().getFullYear(),
            sender_organization_name: senderName,
            registered_at: decidedAt,
        };
        registerDialogOpen.value = false;
    }

    return {
        submission,
        returnDialogOpen,
        rejectDialogOpen,
        registerDialogOpen,
        decide,
        register,
    };
}

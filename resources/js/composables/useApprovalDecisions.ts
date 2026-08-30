import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Ref } from 'vue';
import { toast } from 'vue-sonner';
import type {
    ApprovalDecision,
    ApprovalSubmission,
    RegisterApprovalPayload,
    RejectApprovalPayload,
    ReturnApprovalPayload,
    SenderOrganizationOption,
} from '@/types';

type DecisionPayload =
    ReturnApprovalPayload | RejectApprovalPayload | RegisterApprovalPayload;

export function useApprovalDecisions(
    initialSubmission: ApprovalSubmission,
    previewMode: Ref<boolean>,
    organizations: () => SenderOrganizationOption[],
) {
    const submission = ref<ApprovalSubmission>({ ...initialSubmission });
    const returnDialogOpen = ref(false);
    const rejectDialogOpen = ref(false);
    const registerDialogOpen = ref(false);
    const processing = ref(false);
    const errors = ref<Record<string, string>>({});

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

    function submit(
        payload: DecisionPayload,
        dialog: 'return' | 'reject' | 'register',
    ): void {
        errors.value = {};

        if (previewMode.value) {
            applyPreviewPayload(payload);
            closeDialog(dialog);

            return;
        }

        router.post(submission.value.links.decision, payload, {
            preserveScroll: true,
            onStart: () => {
                processing.value = true;
            },
            onError: (responseErrors) => {
                errors.value = responseErrors;
            },
            onSuccess: () => closeDialog(dialog),
            onFinish: () => {
                processing.value = false;
            },
        });
    }

    function applyPreviewPayload(payload: DecisionPayload): void {
        const decidedAt = new Date().toISOString();
        applyPreviewDecision({
            outcome: payload.outcome,
            note: payload.note,
            decided_by: 'Kepala Bagian Umum',
            decided_at: decidedAt,
        });

        if (payload.outcome !== 'REGISTERED') {
            return;
        }

        const selection = payload.sender_organization;
        const senderName =
            selection.mode === 'new'
                ? selection.name
                : (organizations().find(({ id }) => id === selection.id)
                      ?.name ?? submission.value.sender_organization_name);
        submission.value.registration = {
            agenda_number: payload.agenda_number,
            agenda_year: new Date().getFullYear(),
            sender_organization_name: senderName,
            registered_at: decidedAt,
            official_document: submission.value.document
                ? {
                      version_number: 1,
                      original_filename:
                          submission.value.document.original_filename,
                      mime_type: submission.value.document.mime_type,
                      size_bytes: submission.value.document.size_bytes,
                      sha256: submission.value.document.sha256,
                      recorded_at: decidedAt,
                      source: 'SUBMISSION_DOCUMENT',
                  }
                : null,
        };
    }

    function closeDialog(dialog: 'return' | 'reject' | 'register'): void {
        if (dialog === 'return') {
            returnDialogOpen.value = false;
        }

        if (dialog === 'reject') {
            rejectDialogOpen.value = false;
        }

        if (dialog === 'register') {
            registerDialogOpen.value = false;
        }
    }

    return {
        submission,
        returnDialogOpen,
        rejectDialogOpen,
        registerDialogOpen,
        processing,
        errors,
        decide: (
            payload: ReturnApprovalPayload | RejectApprovalPayload,
            dialog: 'return' | 'reject',
        ) => submit(payload, dialog),
        register: (payload: RegisterApprovalPayload) =>
            submit(payload, 'register'),
    };
}

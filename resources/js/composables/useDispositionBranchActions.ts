import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import type {
    AddDispositionFollowUpPayload,
    CompleteDispositionBranchPayload,
    DispositionBranchLifecycle,
    DispositionInboxDetailRoutes,
    DispositionInboxItem,
} from '@/types';

type BranchAction = 'start' | 'follow_up' | 'complete';

type UseDispositionBranchActionsOptions = {
    previewMode: ComputedRef<boolean>;
    baseBranch: ComputedRef<DispositionBranchLifecycle | null>;
    disposition: ComputedRef<DispositionInboxItem | null>;
    routes: ComputedRef<DispositionInboxDetailRoutes | undefined>;
    errors: Ref<Record<string, string>>;
    successNotice: Ref<string>;
};

export function useDispositionBranchActions({
    previewMode,
    baseBranch,
    disposition,
    routes,
    errors,
    successNotice,
}: UseDispositionBranchActionsOptions) {
    const simulatedBranch = ref<DispositionBranchLifecycle | null>(null);
    const processingAction = ref<BranchAction | null>(null);
    const activeBranch = computed(
        () => simulatedBranch.value ?? baseBranch.value,
    );
    let previewTimer: ReturnType<typeof setTimeout> | null = null;

    function startBranch(): void {
        resetFeedback();

        if (previewMode.value && activeBranch.value) {
            runPreview('start', 550, () => {
                simulatedBranch.value = {
                    ...activeBranch.value!,
                    status: 'IN_PROGRESS',
                    started_at: '2026-09-01T12:04:00+08:00',
                };
                successNotice.value =
                    'Simulasi penanganan dimulai. Tidak ada data backend atau audit yang dibuat.';
            });

            return;
        }

        postBranchAction('start', routes.value?.start, {});
    }

    function addFollowUp(payload: AddDispositionFollowUpPayload): void {
        resetFeedback();

        if (previewMode.value && activeBranch.value && disposition.value) {
            runPreview('follow_up', 550, () => {
                simulatedBranch.value = {
                    ...activeBranch.value!,
                    follow_ups: [
                        ...activeBranch.value!.follow_ups,
                        {
                            note: payload.note,
                            created_at: '2026-09-01T12:18:00+08:00',
                            created_by: currentActor(),
                        },
                    ],
                };
                successNotice.value =
                    'Catatan perkembangan ditambahkan pada simulasi lokal.';
            });

            return;
        }

        postBranchAction('follow_up', routes.value?.follow_up, payload);
    }

    function completeBranch(payload: CompleteDispositionBranchPayload): void {
        resetFeedback();

        if (previewMode.value && activeBranch.value && disposition.value) {
            runPreview('complete', 650, () => {
                simulatedBranch.value = {
                    ...activeBranch.value!,
                    status: 'COMPLETED',
                    completed_at: '2026-09-01T12:26:00+08:00',
                    completion_note: payload.completion_note,
                    completed_by: currentActor(),
                };
                successNotice.value =
                    'Cabang diselesaikan pada simulasi lokal. Cabang lain tetap tidak berubah.';
            });

            return;
        }

        postBranchAction('complete', routes.value?.complete, payload, true);
    }

    function currentActor() {
        const position = disposition.value!.recipient_position;

        return {
            name: position.holder_name ?? 'Pejabat aktif',
            position: position.name,
            unit: position.unit_name,
        };
    }

    function runPreview(
        action: BranchAction,
        delay: number,
        mutation: () => void,
    ): void {
        processingAction.value = action;
        previewTimer = setTimeout(() => {
            mutation();
            processingAction.value = null;
            previewTimer = null;
        }, delay);
    }

    function postBranchAction(
        action: BranchAction,
        url: string | undefined,
        payload: Record<string, string | File | null>,
        forceFormData = false,
    ): void {
        if (processingAction.value) {
            return;
        }

        if (!url) {
            errors.value = {
                branch: 'Endpoint lifecycle cabang belum tersedia. Muat ulang halaman setelah backend M6.3 diaktifkan.',
            };

            return;
        }

        processingAction.value = action;
        router.post(url, payload, {
            forceFormData,
            preserveScroll: true,
            onError: (responseErrors) => {
                errors.value = responseErrors;
            },
            onFinish: () => {
                processingAction.value = null;
            },
        });
    }

    function resetFeedback(): void {
        errors.value = {};
        successNotice.value = '';
    }

    onBeforeUnmount(() => {
        if (previewTimer) {
            clearTimeout(previewTimer);
        }
    });

    return {
        activeBranch,
        processingAction,
        startBranch,
        addFollowUp,
        completeBranch,
    };
}

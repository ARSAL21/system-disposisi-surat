<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FileWarning } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import IntakeAuthorityPanel from '@/components/back-office/intake/IntakeAuthorityPanel.vue';
import IntakeDetailHeader from '@/components/back-office/intake/IntakeDetailHeader.vue';
import ScreeningActionPanel from '@/components/back-office/intake/ScreeningActionPanel.vue';
import ScreeningChecklistCard from '@/components/back-office/intake/ScreeningChecklistCard.vue';
import SenderContactCard from '@/components/back-office/intake/SenderContactCard.vue';
import SubmissionDocumentReview from '@/components/back-office/intake/SubmissionDocumentReview.vue';
import SubmissionOverviewCard from '@/components/back-office/intake/SubmissionOverviewCard.vue';
import SubmissionTimeline from '@/components/back-office/intake/SubmissionTimeline.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { intakeRoutes } from '@/lib/intakePresentation';
import type {
    IntakeReviewOutcome,
    IntakeSubmission,
    ScreeningChecklistItem,
} from '@/types';

const props = defineProps<{ submission: IntakeSubmission }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Penerimaan Surat', href: intakeRoutes.index },
            { title: 'Pemeriksaan Awal', href: '#' },
        ],
    },
});

const checklist = ref<ScreeningChecklistItem[]>([]);
const screeningNote = ref('');
const form = useForm<{
    outcome: IntakeReviewOutcome;
    checklist: Array<{ id: string; checked: boolean }>;
    note: string;
}>({
    outcome: 'READY_FOR_APPROVAL',
    checklist: [],
    note: '',
});

watch(
    () => props.submission,
    (submission) => {
        checklist.value = submission.checklist.map((item) => ({ ...item }));
        screeningNote.value = submission.latest_note ?? '';
        form.clearErrors();
    },
    { immediate: true },
);

const checklistComplete = computed(
    () =>
        checklist.value.length > 0 &&
        checklist.value.every((item) => item.checked),
);

function toggleChecklist(id: string, checked: boolean): void {
    checklist.value = checklist.value.map((item) =>
        item.id === id ? { ...item, checked } : item,
    );
}

function submitScreening(outcome: IntakeReviewOutcome): void {
    form.outcome = outcome;
    form.checklist = checklist.value.map(({ id, checked }) => ({
        id,
        checked,
    }));
    form.note = screeningNote.value;
    form.post(props.submission.links.screen, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Pemeriksaan ${submission.subject}`" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <IntakeDetailHeader :submission="submission" />

        <div
            class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(20rem,0.85fr)]"
        >
            <section class="grid gap-5" aria-label="Isi pengajuan surat">
                <SubmissionOverviewCard :submission="submission" />
                <SubmissionDocumentReview
                    v-if="submission.document"
                    :document="submission.document"
                    :preview-url="submission.links.document_preview"
                    :download-url="submission.links.document_download"
                    :can-download="
                        submission.capabilities.can_download_document
                    "
                />
                <Alert v-else variant="destructive">
                    <FileWarning class="size-4" aria-hidden="true" />
                    <AlertTitle>Dokumen pengajuan tidak tersedia</AlertTitle>
                    <AlertDescription>
                        Pemeriksaan tidak dapat diselesaikan sebelum masalah
                        dokumen diperiksa oleh administrator sistem.
                    </AlertDescription>
                </Alert>
                <Alert
                    v-if="submission.status === 'INTERNAL_REVISION_REQUIRED'"
                    class="border-orange-300 bg-orange-50 text-orange-950 dark:border-orange-900 dark:bg-orange-950/25 dark:text-orange-100"
                >
                    <FileWarning class="size-4" aria-hidden="true" />
                    <AlertTitle>Catatan perbaikan dari Kabag Umum</AlertTitle>
                    <AlertDescription>
                        {{ submission.internal_revision_note }}
                    </AlertDescription>
                </Alert>
                <ScreeningChecklistCard
                    :items="checklist"
                    @toggle="toggleChecklist"
                />
                <ScreeningActionPanel
                    v-model:note="screeningNote"
                    :checklist-complete="checklistComplete"
                    :can-screen="
                        submission.capabilities.can_screen &&
                        Boolean(submission.document)
                    "
                    :processing="form.processing"
                    :internal-revision="
                        submission.status === 'INTERNAL_REVISION_REQUIRED'
                    "
                    :note-error="form.errors.note"
                    :checklist-error="form.errors.checklist"
                    @request-revision="submitScreening('REVISION_REQUIRED')"
                    @mark-ready="submitScreening('READY_FOR_APPROVAL')"
                />
            </section>

            <aside class="grid gap-5" aria-label="Konteks pemeriksaan">
                <IntakeAuthorityPanel />
                <SenderContactCard :submission="submission" />
                <SubmissionTimeline :items="submission.timeline" />
            </aside>
        </div>
    </main>
</template>

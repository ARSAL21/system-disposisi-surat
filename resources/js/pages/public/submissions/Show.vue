<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { MessageSquareWarning } from '@lucide/vue';
import { computed } from 'vue';
import PublicResponseTracker from '@/components/public/submission-detail/PublicResponseTracker.vue';
import SubmissionDetailHeader from '@/components/public/submission-detail/SubmissionDetailHeader.vue';
import SubmissionMetadataPanel from '@/components/public/submission-detail/SubmissionMetadataPanel.vue';
import SubmissionTimeline from '@/components/public/submission-detail/SubmissionTimeline.vue';
import SubmissionActionsPanel from '@/components/public/SubmissionActionsPanel.vue';
import SubmissionDocumentPanel from '@/components/public/SubmissionDocumentPanel.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    previewPublicResponseTracker,
    previewPublicSubmission,
} from '@/lib/outgoingLetterPreview';
import publicRoutes from '@/routes/public';
import type {
    LetterSubmission,
    PublicResponseTracker as ResponseTracker,
} from '@/types';

const props = defineProps<{
    submission?: LetterSubmission;
    responseTracker?: ResponseTracker | null;
    preview?: boolean;
}>();

const previewMode = computed(() => props.preview === true);
const activeSubmission = computed(() =>
    previewMode.value ? previewPublicSubmission : (props.submission ?? null),
);
const activeResponseTracker = computed(() =>
    previewMode.value
        ? previewPublicResponseTracker
        : (props.responseTracker ?? null),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Surat Saya', href: publicRoutes.submissions.index() },
            { title: 'Detail Surat', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="activeSubmission?.subject ?? 'Detail Surat'" />

    <div
        v-if="activeSubmission"
        class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
    >
        <SubmissionDetailHeader :submission="activeSubmission" />
        <PublicResponseTracker
            v-if="activeResponseTracker"
            :tracker="activeResponseTracker"
            :preview="previewMode"
        />
        <div
            class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start"
        >
            <div class="min-w-0 space-y-6">
                <SubmissionMetadataPanel :submission="activeSubmission" />
                <Alert
                    v-if="activeSubmission.status === 'REVISION_REQUIRED'"
                    class="border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100"
                >
                    <MessageSquareWarning class="size-4" aria-hidden="true" />
                    <AlertTitle>Catatan koreksi dari Bagian Umum</AlertTitle>
                    <AlertDescription>{{
                        activeSubmission.revision_note
                    }}</AlertDescription>
                </Alert>
                <Alert
                    v-if="activeSubmission.status === 'REJECTED'"
                    variant="destructive"
                >
                    <MessageSquareWarning class="size-4" aria-hidden="true" />
                    <AlertTitle
                        >Alasan pengajuan tidak dapat dilanjutkan</AlertTitle
                    >
                    <AlertDescription>{{
                        activeSubmission.rejection_note
                    }}</AlertDescription>
                </Alert>
                <SubmissionDocumentPanel
                    :submission="activeSubmission"
                    readonly
                />
                <SubmissionActionsPanel
                    v-if="activeSubmission.capabilities.can_submit"
                    :submission="activeSubmission"
                    show-delete
                />
            </div>
            <SubmissionTimeline :submission="activeSubmission" />
        </div>
    </div>
    <Alert v-else variant="destructive" class="m-4 sm:m-6 lg:m-8">
        <MessageSquareWarning class="size-4" />
        <AlertTitle>Surat tidak tersedia</AlertTitle>
        <AlertDescription
            >Backend tidak mengirimkan data surat dan fixture hanya aktif pada
            mode pratinjau.</AlertDescription
        >
    </Alert>
</template>

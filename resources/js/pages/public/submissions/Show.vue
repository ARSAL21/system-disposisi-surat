<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { MessageSquareWarning } from '@lucide/vue';
import SubmissionDetailHeader from '@/components/public/submission-detail/SubmissionDetailHeader.vue';
import SubmissionMetadataPanel from '@/components/public/submission-detail/SubmissionMetadataPanel.vue';
import SubmissionTimeline from '@/components/public/submission-detail/SubmissionTimeline.vue';
import SubmissionActionsPanel from '@/components/public/SubmissionActionsPanel.vue';
import SubmissionDocumentPanel from '@/components/public/SubmissionDocumentPanel.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import publicRoutes from '@/routes/public';
import type { LetterSubmission } from '@/types';

const props = defineProps<{ submission: LetterSubmission }>();

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
    <Head :title="props.submission.subject" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <SubmissionDetailHeader :submission="submission" />
        <div
            class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start"
        >
            <div class="min-w-0 space-y-6">
                <SubmissionMetadataPanel :submission="submission" />
                <Alert
                    v-if="submission.status === 'REVISION_REQUIRED'"
                    class="border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100"
                >
                    <MessageSquareWarning class="size-4" aria-hidden="true" />
                    <AlertTitle>Catatan koreksi dari Bagian Umum</AlertTitle>
                    <AlertDescription>{{
                        submission.revision_note
                    }}</AlertDescription>
                </Alert>
                <Alert
                    v-if="submission.status === 'REJECTED'"
                    variant="destructive"
                >
                    <MessageSquareWarning class="size-4" aria-hidden="true" />
                    <AlertTitle>Alasan pengajuan tidak dapat dilanjutkan</AlertTitle>
                    <AlertDescription>{{
                        submission.rejection_note
                    }}</AlertDescription>
                </Alert>
                <SubmissionDocumentPanel :submission="submission" readonly />
                <SubmissionActionsPanel
                    v-if="submission.capabilities.can_submit"
                    :submission="submission"
                    show-delete
                />
            </div>
            <SubmissionTimeline :submission="submission" />
        </div>
    </div>
</template>

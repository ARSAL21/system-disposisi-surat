<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SubmissionDetailHeader from '@/components/public/submission-detail/SubmissionDetailHeader.vue';
import SubmissionMetadataPanel from '@/components/public/submission-detail/SubmissionMetadataPanel.vue';
import SubmissionTimeline from '@/components/public/submission-detail/SubmissionTimeline.vue';
import SubmissionActionsPanel from '@/components/public/SubmissionActionsPanel.vue';
import SubmissionDocumentPanel from '@/components/public/SubmissionDocumentPanel.vue';
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
                <SubmissionDocumentPanel :submission="submission" readonly />
                <SubmissionActionsPanel
                    v-if="submission.status === 'DRAFT'"
                    :submission="submission"
                    show-delete
                />
            </div>
            <SubmissionTimeline :submission="submission" />
        </div>
    </div>
</template>

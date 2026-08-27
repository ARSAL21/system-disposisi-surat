<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SubmissionEmptyState from '@/components/public/submission-index/SubmissionEmptyState.vue';
import SubmissionIndexHeader from '@/components/public/submission-index/SubmissionIndexHeader.vue';
import SubmissionPagination from '@/components/public/submission-index/SubmissionPagination.vue';
import SubmissionTable from '@/components/public/submission-index/SubmissionTable.vue';
import SubmissionCard from '@/components/public/SubmissionCard.vue';
import publicRoutes from '@/routes/public';
import type { PaginatedSubmissions } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Surat Saya',
                href: publicRoutes.submissions.index(),
            },
        ],
    },
});

defineProps<{ submissions: PaginatedSubmissions }>();
</script>

<template>
    <Head title="Surat Saya" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <SubmissionIndexHeader />

        <template v-if="submissions.data.length">
            <div class="grid gap-4 md:hidden">
                <SubmissionCard
                    v-for="submission in submissions.data"
                    :key="submission.public_id"
                    :submission="submission"
                />
            </div>
            <SubmissionTable :submissions="submissions.data" />
            <SubmissionPagination :pagination="submissions" />
        </template>

        <SubmissionEmptyState v-else />
    </div>
</template>

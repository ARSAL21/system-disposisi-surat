<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LetterActivityDetailDialog from '@/components/back-office/letter-activities/LetterActivityDetailDialog.vue';
import LetterActivityFilterPanel from '@/components/back-office/letter-activities/LetterActivityFilterPanel.vue';
import LetterActivityHeader from '@/components/back-office/letter-activities/LetterActivityHeader.vue';
import LetterActivityList from '@/components/back-office/letter-activities/LetterActivityList.vue';
import LetterActivityPagination from '@/components/back-office/letter-activities/LetterActivityPagination.vue';
import LetterActivitySummaryCards from '@/components/back-office/letter-activities/LetterActivitySummaryCards.vue';
import LetterActivityVisibilityNotice from '@/components/back-office/letter-activities/LetterActivityVisibilityNotice.vue';
import { useLetterActivityWorkspace } from '@/composables/useLetterActivityWorkspace';
import type { LetterActivityPageProps } from '@/composables/useLetterActivityWorkspace';

const props = defineProps<LetterActivityPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Aktivitas Surat',
                href: '/back-office/audits/letters',
            },
        ],
    },
});

const {
    activeFilters,
    dateLabel,
    detailOpen,
    filtered,
    options,
    pagination,
    previewMode,
    processing,
    resetFilters,
    selectedActivity,
    showDetail,
    summary,
    timezone,
    visibility,
    visit,
} = useLetterActivityWorkspace(props);
</script>

<template>
    <Head title="Aktivitas Surat" />

    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <LetterActivityHeader
            :date-label="dateLabel"
            timezone-label="Waktu Indonesia Tengah"
        />
        <LetterActivityVisibilityNotice
            :visibility="visibility"
            :preview="previewMode"
        />
        <LetterActivitySummaryCards :summary="summary" />
        <LetterActivityFilterPanel
            :filters="activeFilters"
            :options="options"
            :processing="processing"
            :visibility="visibility"
            @apply="visit($event)"
            @reset="resetFilters"
        />
        <LetterActivityList
            :activities="pagination.data"
            :filtered="filtered"
            :timezone="timezone"
            :visibility="visibility"
            @detail="showDetail"
            @reset="resetFilters"
        >
            <template #pagination>
                <LetterActivityPagination
                    :pagination="pagination"
                    :processing="processing"
                    @page="visit(activeFilters, $event)"
                />
            </template>
        </LetterActivityList>
        <LetterActivityDetailDialog
            v-model:open="detailOpen"
            :activity="selectedActivity"
            :timezone="timezone"
            :visibility="visibility"
        />
    </main>
</template>

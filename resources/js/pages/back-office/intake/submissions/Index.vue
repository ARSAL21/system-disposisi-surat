<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import IntakeFilterBar from '@/components/back-office/intake/IntakeFilterBar.vue';
import IntakePageHeader from '@/components/back-office/intake/IntakePageHeader.vue';
import IntakePagination from '@/components/back-office/intake/IntakePagination.vue';
import IntakeSubmissionList from '@/components/back-office/intake/IntakeSubmissionList.vue';
import IntakeSummaryCards from '@/components/back-office/intake/IntakeSummaryCards.vue';
import { intakeRoutes } from '@/lib/intakePresentation';
import type {
    IntakeFilters,
    IntakePagination as PaginationData,
    IntakeSummary,
    PaginatedIntakeSubmissions,
} from '@/types';

const props = defineProps<{
    submissions: PaginatedIntakeSubmissions;
    summary: IntakeSummary;
    filters: IntakeFilters;
    routes: { index: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Penerimaan Surat', href: intakeRoutes.index },
        ],
    },
});

const filters = reactive<IntakeFilters>({ ...props.filters });
const pagination = computed<PaginationData>(() => ({
    current_page: props.submissions.meta.current_page,
    last_page: props.submissions.meta.last_page,
    from: props.submissions.meta.from ?? 0,
    to: props.submissions.meta.to ?? 0,
    total: props.submissions.meta.total,
    previous_url: props.submissions.links.prev,
    next_url: props.submissions.links.next,
}));

const refreshQueue = useDebounceFn(() => {
    router.get(
        props.routes.index,
        { ...filters },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}, 300);

function updateFilters(patch: Partial<IntakeFilters>): void {
    Object.assign(filters, patch);
    void refreshQueue();
}

function resetFilters(): void {
    Object.assign(filters, {
        search: '',
        status: 'SUBMITTED',
        source: 'all',
        date_from: '',
        date_to: '',
    } satisfies IntakeFilters);
    void refreshQueue();
}
</script>

<template>
    <Head title="Penerimaan Surat" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <IntakePageHeader />
        <IntakeSummaryCards :summary="summary" />
        <IntakeFilterBar
            :filters="filters"
            @change="updateFilters"
            @reset="resetFilters"
        />
        <IntakeSubmissionList
            :submissions="submissions.data"
            @reset="resetFilters"
        >
            <template #pagination>
                <IntakePagination :pagination="pagination" />
            </template>
        </IntakeSubmissionList>
    </main>
</template>

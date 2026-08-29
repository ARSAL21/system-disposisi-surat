<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import ApprovalFilterBar from '@/components/back-office/intake/approval/ApprovalFilterBar.vue';
import ApprovalPageHeader from '@/components/back-office/intake/approval/ApprovalPageHeader.vue';
import ApprovalPagination from '@/components/back-office/intake/approval/ApprovalPagination.vue';
import ApprovalSubmissionList from '@/components/back-office/intake/approval/ApprovalSubmissionList.vue';
import ApprovalSummaryCards from '@/components/back-office/intake/approval/ApprovalSummaryCards.vue';
import {
    previewApprovalSubmissions,
    previewApprovalSummary,
} from '@/lib/intakeApprovalPreview';
import { approvalRoutes } from '@/lib/intakeApprovalPresentation';
import type {
    ApprovalFilters,
    ApprovalPagination as PaginationData,
    ApprovalSummary,
    PaginatedApprovalSubmissions,
} from '@/types';

const props = defineProps<{
    submissions?: PaginatedApprovalSubmissions;
    summary?: ApprovalSummary;
    filters?: ApprovalFilters;
    routes?: { index: string };
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Persetujuan Surat', href: approvalRoutes.index },
        ],
    },
});

const previewMode = computed(() => props.preview ?? !props.submissions);
const filters = reactive<ApprovalFilters>({
    tab: props.filters?.tab ?? 'pending',
    search: props.filters?.search ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
});

const previewSubmissions = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return previewApprovalSubmissions.filter((submission) => {
        const isPending = submission.status === 'READY_FOR_APPROVAL';
        const matchesTab = filters.tab === 'pending' ? isPending : !isPending;
        const matchesSearch =
            !search ||
            [
                submission.subject,
                submission.sender_organization_name,
                submission.external_letter_number ?? '',
            ].some((value) =>
                value.toLocaleLowerCase('id-ID').includes(search),
            );
        const date = submission.submitted_at.slice(0, 10);

        return (
            matchesTab &&
            matchesSearch &&
            (!filters.date_from || date >= filters.date_from) &&
            (!filters.date_to || date <= filters.date_to)
        );
    });
});

const submissions = computed(() =>
    previewMode.value
        ? previewSubmissions.value
        : (props.submissions?.data ?? []),
);
const summary = computed(() => props.summary ?? previewApprovalSummary);
const pagination = computed<PaginationData>(() =>
    previewMode.value
        ? {
              current_page: 1,
              last_page: 1,
              from: submissions.value.length > 0 ? 1 : 0,
              to: submissions.value.length,
              total: submissions.value.length,
              previous_url: null,
              next_url: null,
          }
        : (props.submissions?.pagination ?? {
              current_page: 1,
              last_page: 1,
              from: 0,
              to: 0,
              total: 0,
              previous_url: null,
              next_url: null,
          }),
);

const refreshQueue = useDebounceFn(() => {
    if (previewMode.value || !props.routes?.index) return;
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

function updateFilters(patch: Partial<ApprovalFilters>): void {
    Object.assign(filters, patch);
    void refreshQueue();
}

function resetFilters(): void {
    Object.assign(filters, {
        tab: 'pending',
        search: '',
        date_from: '',
        date_to: '',
    } satisfies ApprovalFilters);
    void refreshQueue();
}
</script>

<template>
    <Head title="Persetujuan Surat" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <ApprovalPageHeader :preview="previewMode" />
        <ApprovalSummaryCards :summary="summary" />
        <ApprovalFilterBar
            :filters="filters"
            @change="updateFilters"
            @reset="resetFilters"
        />
        <ApprovalSubmissionList
            :submissions="submissions"
            :tab="filters.tab"
            @reset="resetFilters"
        >
            <template #pagination>
                <ApprovalPagination :pagination="pagination" />
            </template>
        </ApprovalSubmissionList>
    </main>
</template>

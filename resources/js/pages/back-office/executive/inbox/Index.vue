<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import ExecutiveInboxFilterPanel from '@/components/back-office/routing/ExecutiveInboxFilterPanel.vue';
import ExecutiveInboxList from '@/components/back-office/routing/ExecutiveInboxList.vue';
import RoutingPagination from '@/components/back-office/routing/RoutingPagination.vue';
import RoutingSummaryCards from '@/components/back-office/routing/RoutingSummaryCards.vue';
import RoutingWorkspaceHeader from '@/components/back-office/routing/RoutingWorkspaceHeader.vue';
import {
    previewExecutiveInboxItems,
    previewExecutiveInboxSummary,
} from '@/lib/letterRoutingPreview';
import type {
    ExecutiveInboxFilters,
    ExecutiveInboxSummary,
    PaginatedExecutiveInbox,
    RoutingPagination as PaginationData,
} from '@/types';

const props = defineProps<{
    inbox?: PaginatedExecutiveInbox;
    summary?: ExecutiveInboxSummary;
    filters?: ExecutiveInboxFilters;
    routes?: { index: string };
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Inbox Pimpinan', href: '/back-office/executive/inbox' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const filters = reactive<ExecutiveInboxFilters>({
    search: props.filters?.search ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
});

const previewRoutes = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return previewExecutiveInboxItems.filter((route) => {
        const matchesSearch =
            !search ||
            [
                route.letter.agenda_number,
                route.letter.subject,
                route.letter.sender_organization_name,
                route.letter.external_letter_number ?? '',
            ].some((value) =>
                value.toLocaleLowerCase('id-ID').includes(search),
            );
        const inboxDate = route.received_in_inbox_at.slice(0, 10);

        return (
            matchesSearch &&
            (!filters.date_from || inboxDate >= filters.date_from) &&
            (!filters.date_to || inboxDate <= filters.date_to)
        );
    });
});

const inboxRoutes = computed(() =>
    previewMode.value ? previewRoutes.value : (props.inbox?.data ?? []),
);
const summary = computed<ExecutiveInboxSummary>(() =>
    previewMode.value
        ? previewExecutiveInboxSummary
        : (props.summary ?? { pending: 0, received_today: 0 }),
);
const pagination = computed<PaginationData>(() =>
    previewMode.value
        ? {
              current_page: 1,
              last_page: 1,
              from: inboxRoutes.value.length > 0 ? 1 : 0,
              to: inboxRoutes.value.length,
              total: inboxRoutes.value.length,
              previous_url: null,
              next_url: null,
          }
        : (props.inbox?.pagination ?? {
              current_page: 1,
              last_page: 1,
              from: 0,
              to: 0,
              total: 0,
              previous_url: null,
              next_url: null,
          }),
);

const refreshInbox = useDebounceFn(() => {
    if (previewMode.value || !props.routes?.index) {
        return;
    }

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

function updateFilters(patch: Partial<ExecutiveInboxFilters>): void {
    Object.assign(filters, patch);
    void refreshInbox();
}

function resetFilters(): void {
    Object.assign(filters, {
        search: '',
        date_from: '',
        date_to: '',
    } satisfies ExecutiveInboxFilters);
    void refreshInbox();
}
</script>

<template>
    <Head title="Inbox Pimpinan" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <RoutingWorkspaceHeader mode="inbox" :preview="previewMode" />
        <RoutingSummaryCards mode="inbox" :inbox-summary="summary" />
        <ExecutiveInboxFilterPanel
            :filters="filters"
            @change="updateFilters"
            @reset="resetFilters"
        />
        <ExecutiveInboxList :routes="inboxRoutes" @reset="resetFilters">
            <template #pagination>
                <RoutingPagination :pagination="pagination" />
            </template>
        </ExecutiveInboxList>
    </main>
</template>

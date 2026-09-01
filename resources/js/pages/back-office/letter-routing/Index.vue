<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import RoutingFilterPanel from '@/components/back-office/routing/RoutingFilterPanel.vue';
import RoutingPagination from '@/components/back-office/routing/RoutingPagination.vue';
import RoutingQueueList from '@/components/back-office/routing/RoutingQueueList.vue';
import RoutingSummaryCards from '@/components/back-office/routing/RoutingSummaryCards.vue';
import RoutingWorkspaceHeader from '@/components/back-office/routing/RoutingWorkspaceHeader.vue';
import {
    previewLetterRoutingItems,
    previewLetterRoutingSummary,
} from '@/lib/letterRoutingPreview';
import type {
    LetterRoutingFilters,
    LetterRoutingSummary,
    PaginatedLetterRouting,
    RoutingPagination as PaginationData,
} from '@/types';

const props = defineProps<{
    letters?: PaginatedLetterRouting;
    summary?: LetterRoutingSummary;
    filters?: LetterRoutingFilters;
    routes?: { index: string };
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Routing Surat', href: '/back-office/letter-routing' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const filters = reactive<LetterRoutingFilters>({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const previewLetters = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return previewLetterRoutingItems.filter((letter) => {
        const matchesSearch =
            !search ||
            [
                letter.agenda_number,
                letter.subject,
                letter.sender_organization_name,
                letter.external_letter_number ?? '',
            ].some((value) =>
                value.toLocaleLowerCase('id-ID').includes(search),
            );

        return (
            matchesSearch &&
            (!filters.status || letter.status === filters.status)
        );
    });
});

const letters = computed(() =>
    previewMode.value ? previewLetters.value : (props.letters?.data ?? []),
);
const summary = computed<LetterRoutingSummary>(() =>
    previewMode.value
        ? previewLetterRoutingSummary
        : (props.summary ?? {
              awaiting_route: 0,
              pending_executive: 0,
              routed_today: 0,
          }),
);
const pagination = computed<PaginationData>(() =>
    previewMode.value
        ? {
              current_page: 1,
              last_page: 1,
              from: letters.value.length > 0 ? 1 : 0,
              to: letters.value.length,
              total: letters.value.length,
              previous_url: null,
              next_url: null,
          }
        : (props.letters?.pagination ?? {
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

function updateFilters(patch: Partial<LetterRoutingFilters>): void {
    Object.assign(filters, patch);
    void refreshQueue();
}

function resetFilters(): void {
    Object.assign(filters, {
        search: '',
        status: '',
    } satisfies LetterRoutingFilters);
    void refreshQueue();
}
</script>

<template>
    <Head title="Routing Surat ke Pimpinan" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <RoutingWorkspaceHeader mode="routing" :preview="previewMode" />
        <RoutingSummaryCards mode="routing" :routing-summary="summary" />
        <RoutingFilterPanel
            :filters="filters"
            @change="updateFilters"
            @reset="resetFilters"
        />
        <RoutingQueueList :letters="letters" @reset="resetFilters">
            <template #pagination>
                <RoutingPagination :pagination="pagination" />
            </template>
        </RoutingQueueList>
    </main>
</template>

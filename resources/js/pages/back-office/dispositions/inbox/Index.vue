<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import DispositionInboxFilterPanel from '@/components/back-office/dispositions/DispositionInboxFilterPanel.vue';
import DispositionInboxHeader from '@/components/back-office/dispositions/DispositionInboxHeader.vue';
import DispositionInboxList from '@/components/back-office/dispositions/DispositionInboxList.vue';
import DispositionInboxSummary from '@/components/back-office/dispositions/DispositionInboxSummary.vue';
import RoutingPagination from '@/components/back-office/routing/RoutingPagination.vue';
import {
    previewDispositionInboxItems,
    previewDispositionInboxSummary,
} from '@/lib/dispositionPreview';
import type {
    DispositionInboxFilters,
    DispositionInboxRoutes,
    DispositionInboxSummary as DispositionSummary,
    PaginatedDispositionInbox,
    RoutingPagination as PaginationData,
} from '@/types';

const props = defineProps<{
    inbox?: PaginatedDispositionInbox;
    summary?: DispositionSummary;
    filters?: DispositionInboxFilters;
    routes?: DispositionInboxRoutes;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Inbox Disposisi', href: '/back-office/dispositions/inbox' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const filters = reactive<DispositionInboxFilters>({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
});

const previewItems = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return previewDispositionInboxItems.filter((disposition) => {
        const matchesSearch =
            !search ||
            [
                disposition.letter.agenda_number,
                disposition.letter.subject,
                disposition.letter.sender_organization_name,
                disposition.sender.name,
            ].some((value) =>
                value.toLocaleLowerCase('id-ID').includes(search),
            );
        const receivedDate = disposition.received_at.slice(0, 10);

        return (
            matchesSearch &&
            (!filters.status || disposition.status === filters.status) &&
            (!filters.date_from || receivedDate >= filters.date_from) &&
            (!filters.date_to || receivedDate <= filters.date_to)
        );
    });
});

const dispositions = computed(() =>
    previewMode.value ? previewItems.value : (props.inbox?.data ?? []),
);
const summary = computed<DispositionSummary>(() =>
    previewMode.value
        ? previewDispositionInboxSummary
        : (props.summary ?? {
              pending: 0,
              in_progress: 0,
              received_today: 0,
          }),
);
const pagination = computed<PaginationData>(() =>
    previewMode.value
        ? {
              current_page: 1,
              last_page: 1,
              from: dispositions.value.length > 0 ? 1 : 0,
              to: dispositions.value.length,
              total: dispositions.value.length,
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

function updateFilters(patch: Partial<DispositionInboxFilters>): void {
    Object.assign(filters, patch);
    void refreshInbox();
}

function resetFilters(): void {
    Object.assign(filters, {
        search: '',
        status: '',
        date_from: '',
        date_to: '',
    } satisfies DispositionInboxFilters);
    void refreshInbox();
}
</script>

<template>
    <Head title="Inbox Disposisi" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <DispositionInboxHeader :preview="previewMode" />
        <DispositionInboxSummary :summary="summary" />
        <DispositionInboxFilterPanel
            :filters="filters"
            @change="updateFilters"
            @reset="resetFilters"
        />
        <DispositionInboxList
            :dispositions="dispositions"
            @reset="resetFilters"
        >
            <template #pagination>
                <RoutingPagination :pagination="pagination" />
            </template>
        </DispositionInboxList>
    </main>
</template>

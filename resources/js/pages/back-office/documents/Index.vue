<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import DocumentArchiveFilterPanel from '@/components/back-office/documents/DocumentArchiveFilterPanel.vue';
import DocumentArchiveHeader from '@/components/back-office/documents/DocumentArchiveHeader.vue';
import DocumentArchiveList from '@/components/back-office/documents/DocumentArchiveList.vue';
import DocumentArchivePagination from '@/components/back-office/documents/DocumentArchivePagination.vue';
import DocumentArchiveSummaryCards from '@/components/back-office/documents/DocumentArchiveSummaryCards.vue';
import {
    previewDocumentArchiveItems,
    previewDocumentArchiveSummary,
} from '@/lib/documentArchivePreview';
import type {
    DocumentArchiveFilters,
    DocumentArchivePagination as PaginationData,
    DocumentArchiveSummary,
    PaginatedDocumentArchive,
} from '@/types';

const props = defineProps<{
    documents?: PaginatedDocumentArchive;
    summary?: DocumentArchiveSummary;
    filters?: DocumentArchiveFilters;
    routes?: { index: string };
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Arsip Dokumen', href: '/back-office/documents' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const filters = reactive<DocumentArchiveFilters>({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
});

const previewDocuments = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return previewDocumentArchiveItems.filter((document) => {
        const matchesSearch =
            !search ||
            [
                document.agenda_number,
                document.subject,
                document.sender_organization_name,
                document.current_version.original_filename,
            ].some((value) =>
                value.toLocaleLowerCase('id-ID').includes(search),
            );
        const receivedDate = document.received_at.slice(0, 10);

        return (
            matchesSearch &&
            (!filters.status || document.status === filters.status) &&
            (!filters.date_from || receivedDate >= filters.date_from) &&
            (!filters.date_to || receivedDate <= filters.date_to)
        );
    });
});

const documents = computed(() =>
    previewMode.value ? previewDocuments.value : (props.documents?.data ?? []),
);
const summary = computed<DocumentArchiveSummary>(() =>
    previewMode.value
        ? previewDocumentArchiveSummary
        : (props.summary ?? {
              total_letters: 0,
              corrected_letters: 0,
              total_versions: 0,
              updated_this_month: 0,
          }),
);
const pagination = computed<PaginationData>(() =>
    previewMode.value
        ? {
              current_page: 1,
              last_page: 1,
              from: documents.value.length > 0 ? 1 : 0,
              to: documents.value.length,
              total: documents.value.length,
              previous_url: null,
              next_url: null,
          }
        : (props.documents?.pagination ?? {
              current_page: 1,
              last_page: 1,
              from: 0,
              to: 0,
              total: 0,
              previous_url: null,
              next_url: null,
          }),
);

const refreshArchive = useDebounceFn(() => {
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

function updateFilters(patch: Partial<DocumentArchiveFilters>): void {
    Object.assign(filters, patch);
    void refreshArchive();
}

function resetFilters(): void {
    Object.assign(filters, {
        search: '',
        status: '',
        date_from: '',
        date_to: '',
    } satisfies DocumentArchiveFilters);
    void refreshArchive();
}
</script>

<template>
    <Head title="Arsip Dokumen Surat" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <DocumentArchiveHeader :preview="previewMode" />
        <DocumentArchiveSummaryCards :summary="summary" />
        <DocumentArchiveFilterPanel
            :filters="filters"
            @change="updateFilters"
            @reset="resetFilters"
        />
        <DocumentArchiveList :documents="documents" @reset="resetFilters">
            <template #pagination>
                <DocumentArchivePagination :pagination="pagination" />
            </template>
        </DocumentArchiveList>
    </main>
</template>

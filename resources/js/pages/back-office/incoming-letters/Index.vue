<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import IncomingRegisterFilters from '@/components/back-office/incoming-register/IncomingRegisterFilters.vue';
import IncomingRegisterHeader from '@/components/back-office/incoming-register/IncomingRegisterHeader.vue';
import IncomingRegisterList from '@/components/back-office/incoming-register/IncomingRegisterList.vue';
import IncomingRegisterPagination from '@/components/back-office/incoming-register/IncomingRegisterPagination.vue';
import IncomingRegisterSummary from '@/components/back-office/incoming-register/IncomingRegisterSummary.vue';
import {
    previewIncomingRegisterFilters,
    previewIncomingRegisterItems,
    previewIncomingRegisterSummary,
} from '@/lib/incomingRegisterPreview';
import type {
    IncomingRegisterFilters as RegisterFilters,
    IncomingRegisterPageProps,
    IncomingRegisterPagination as RegisterPagination,
} from '@/types';

const props = defineProps<IncomingRegisterPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Buku Surat Masuk',
                href: '/back-office/incoming-letters',
            },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const filters = reactive<RegisterFilters>({
    search:
        props.filters?.search ??
        (previewMode.value ? previewIncomingRegisterFilters.search : ''),
    source:
        props.filters?.source ??
        (previewMode.value ? previewIncomingRegisterFilters.source : ''),
    status:
        props.filters?.status ??
        (previewMode.value ? previewIncomingRegisterFilters.status : ''),
    year:
        props.filters?.year ??
        (previewMode.value ? previewIncomingRegisterFilters.year : ''),
    date_from:
        props.filters?.date_from ??
        (previewMode.value ? previewIncomingRegisterFilters.date_from : ''),
    date_to:
        props.filters?.date_to ??
        (previewMode.value ? previewIncomingRegisterFilters.date_to : ''),
});

const previewLetters = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return previewIncomingRegisterItems.filter((letter) => {
        const receivedDate = letter.received_at.slice(0, 10);
        const matchesSearch =
            !search ||
            [
                letter.agenda_number,
                letter.external_letter_number ?? '',
                letter.sender_organization_name,
                letter.contact_name,
                letter.subject,
                letter.document?.original_filename ?? '',
            ].some((value) =>
                value.toLocaleLowerCase('id-ID').includes(search),
            );

        return (
            matchesSearch &&
            (!filters.source || letter.source === filters.source) &&
            (!filters.status || letter.status === filters.status) &&
            (!filters.year || String(letter.agenda_year) === filters.year) &&
            (!filters.date_from || receivedDate >= filters.date_from) &&
            (!filters.date_to || receivedDate <= filters.date_to)
        );
    });
});

const letters = computed(() =>
    previewMode.value ? previewLetters.value : (props.letters?.data ?? []),
);
const summary = computed(() =>
    previewMode.value
        ? previewIncomingRegisterSummary
        : (props.summary ?? {
              total_letters: 0,
              online_letters: 0,
              manual_letters: 0,
              received_today: 0,
          }),
);
const pagination = computed<RegisterPagination>(() =>
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
const createManualUrl = computed<string | null>(() =>
    previewMode.value
        ? '/back-office/previews/intake/manual/create'
        : (props.routes?.create_manual ?? null),
);

const refreshRegister = useDebounceFn(() => {
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

function updateFilters(patch: Partial<RegisterFilters>): void {
    Object.assign(filters, patch);
    void refreshRegister();
}

function resetFilters(): void {
    Object.assign(filters, {
        search: '',
        source: '',
        status: '',
        year: '',
        date_from: '',
        date_to: '',
    } satisfies RegisterFilters);
    void refreshRegister();
}
</script>

<template>
    <Head title="Buku Agenda Surat Masuk" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <IncomingRegisterHeader
            :create-manual-url="createManualUrl"
            :preview="previewMode"
        />
        <IncomingRegisterSummary :summary="summary" />
        <IncomingRegisterFilters
            :filters="filters"
            @change="updateFilters"
            @reset="resetFilters"
        />
        <IncomingRegisterList :letters="letters" @reset="resetFilters">
            <template #pagination>
                <IncomingRegisterPagination :pagination="pagination" />
            </template>
        </IncomingRegisterList>
    </main>
</template>

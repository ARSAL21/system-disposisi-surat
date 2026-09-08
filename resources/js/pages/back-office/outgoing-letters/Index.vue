<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import OutgoingRegisterFilters from '@/components/back-office/outgoing-letters/OutgoingRegisterFilters.vue';
import OutgoingRegisterHeader from '@/components/back-office/outgoing-letters/OutgoingRegisterHeader.vue';
import OutgoingRegisterList from '@/components/back-office/outgoing-letters/OutgoingRegisterList.vue';
import OutgoingRegisterPagination from '@/components/back-office/outgoing-letters/OutgoingRegisterPagination.vue';
import OutgoingRegisterSummary from '@/components/back-office/outgoing-letters/OutgoingRegisterSummary.vue';
import { previewOutgoingFilters, previewOutgoingLetters, previewOutgoingSummary } from '@/lib/outgoingLetterPreview';
import type { OutgoingLetterFilters, OutgoingLetterPagination, OutgoingRegisterPageProps } from '@/types';

const props = defineProps<OutgoingRegisterPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Register Surat Keluar', href: '/back-office/outgoing-letters' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const filters = reactive<OutgoingLetterFilters>({
    search: props.filters?.search ?? (previewMode.value ? previewOutgoingFilters.search : ''),
    status: props.filters?.status ?? (previewMode.value ? previewOutgoingFilters.status : ''),
    source: props.filters?.source ?? (previewMode.value ? previewOutgoingFilters.source : ''),
    year: props.filters?.year ?? (previewMode.value ? previewOutgoingFilters.year : ''),
});

const previewLetters = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return previewOutgoingLetters.filter((letter) =>
        (!search || [letter.subject, letter.incoming_agenda_number, letter.sender_organization_name, letter.outgoing_number ?? ''].some((value) => value.toLocaleLowerCase('id-ID').includes(search)))
        && (!filters.status || letter.status === filters.status)
        && (!filters.source || letter.source === filters.source)
        && (!filters.year || String(new Date(letter.authorized_at).getFullYear()) === filters.year),
    );
});

const letters = computed(() => previewMode.value ? previewLetters.value : (props.letters?.data ?? []));
const summary = computed(() => previewMode.value ? previewOutgoingSummary : (props.summary ?? { total: 0, awaiting_number: 0, awaiting_verification: 0, ready_for_delivery: 0, delivered_this_month: 0 }));
const pagination = computed<OutgoingLetterPagination>(() => previewMode.value
    ? { current_page: 1, last_page: 1, from: letters.value.length ? 1 : 0, to: letters.value.length, total: letters.value.length, previous_url: null, next_url: null }
    : (props.letters?.pagination ?? { current_page: 1, last_page: 1, from: 0, to: 0, total: 0, previous_url: null, next_url: null }));

const refresh = useDebounceFn(() => {
    if (previewMode.value || !props.routes?.index) {
        return;
    }

    router.get(props.routes.index, { ...filters }, { preserveScroll: true, preserveState: true, replace: true });
}, 300);

function updateFilters(patch: Partial<OutgoingLetterFilters>): void {
    Object.assign(filters, patch);
    void refresh();
}

function resetFilters(): void {
    Object.assign(filters, { search: '', status: '', source: '', year: '' } satisfies OutgoingLetterFilters);
    void refresh();
}
</script>

<template>
    <Head title="Register Surat Keluar" />
    <main class="flex flex-1 flex-col gap-5 bg-muted/15 p-4 sm:p-6 lg:p-8">
        <div class="mx-auto flex w-full max-w-[100rem] flex-col gap-5">
            <OutgoingRegisterHeader />
            <OutgoingRegisterSummary :summary="summary" />
            <OutgoingRegisterFilters :filters="filters" @change="updateFilters" @reset="resetFilters" />
            <OutgoingRegisterList :letters="letters" @reset="resetFilters">
                <template #pagination><OutgoingRegisterPagination :pagination="pagination" /></template>
            </OutgoingRegisterList>
        </div>
    </main>
</template>

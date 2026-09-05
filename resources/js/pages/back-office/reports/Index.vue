<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive } from 'vue';
import { toast } from 'vue-sonner';
import PeriodicReportGraphWorkspace from '@/components/back-office/reports/PeriodicReportGraphWorkspace.vue';
import {
    previewPeriodicReportFilters,
    previewPeriodicReportIntakeFunnel,
    previewPeriodicReportLetters,
    previewPeriodicReportOrganizationGraph,
    previewPeriodicReportScope,
    previewPeriodicReportSenderBreakdown,
    previewPeriodicReportSourceBreakdown,
    previewPeriodicReportSummary,
    previewPeriodicReportTrend,
} from '@/lib/periodicReportPreview';
import type {
    PaginatedPeriodicReportLetters,
    PeriodicReportFilters as ReportFilters,
    PeriodicReportPageProps,
} from '@/types';

const props = defineProps<PeriodicReportPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Laporan Periodik', href: '/back-office/reports' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);

function currentMonthFilters(): ReportFilters {
    const now = new Date();
    const parts = new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        timeZone: 'Asia/Makassar',
    }).formatToParts(now);
    const part = (type: Intl.DateTimeFormatPartTypes): string =>
        parts.find((item) => item.type === type)?.value ?? '';
    const year = Number(part('year'));
    const month = Number(part('month'));
    const monthValue = String(month).padStart(2, '0');
    const lastDay = new Date(Date.UTC(year, month, 0)).getUTCDate();

    return {
        date_from: `${year}-${monthValue}-01`,
        date_to: `${year}-${monthValue}-${String(lastDay).padStart(2, '0')}`,
        source: '',
        event: 'RECEIVED',
        status: '',
        search: '',
    };
}

const initialFilters = computed(() =>
    previewMode.value
        ? previewPeriodicReportFilters
        : (props.filters ?? currentMonthFilters()),
);
const activeFilters = reactive<ReportFilters>({ ...initialFilters.value });

const scope = computed(() =>
    previewMode.value
        ? previewPeriodicReportScope
        : (props.scope ?? {
              mode: 'SECTION_HEAD_BRANCHES' as const,
              label: 'Cakupan laporan belum tersedia',
              description: 'Backend belum mengirimkan cakupan laporan.',
          }),
);
const summary = computed(() =>
    previewMode.value
        ? previewPeriodicReportSummary
        : (props.summary ?? {
              received_letters: 0,
              processing_started: 0,
              completed_letters: 0,
              average_completion_hours: null,
          }),
);
const graph = computed(() =>
    previewMode.value
        ? previewPeriodicReportOrganizationGraph
        : (props.organizationGraph ?? { generated_at: '', executives: [] }),
);
const trend = computed(() =>
    previewMode.value ? previewPeriodicReportTrend : (props.trend ?? []),
);
const sourceBreakdown = computed(() =>
    previewMode.value
        ? previewPeriodicReportSourceBreakdown
        : (props.sourceBreakdown ?? []),
);
const intakeFunnel = computed(() =>
    previewMode.value
        ? previewPeriodicReportIntakeFunnel
        : (props.intakeFunnel ?? {
              online_submissions: 0,
              manual_submissions: 0,
              converted_to_letters: 0,
          }),
);
const senderBreakdown = computed(() =>
    previewMode.value
        ? previewPeriodicReportSenderBreakdown
        : (props.senderBreakdown ?? []),
);
const letters = computed<PaginatedPeriodicReportLetters>(() =>
    previewMode.value
        ? previewPeriodicReportLetters
        : (props.letters ?? {
              data: [],
              pagination: {
                  current_page: 1,
                  last_page: 1,
                  from: 0,
                  to: 0,
                  total: 0,
                  previous_url: null,
                  next_url: null,
              },
          }),
);

const refreshReport = useDebounceFn(() => {
    if (previewMode.value || !props.routes?.index) {
        return;
    }

    router.get(
        props.routes.index,
        { ...activeFilters },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}, 350);

function updateFilters(patch: Partial<ReportFilters>): void {
    Object.assign(activeFilters, patch);

    void refreshReport();
}

function resetFilters(): void {
    Object.assign(activeFilters, initialFilters.value);

    void refreshReport();
}

function exportReport(kind: 'summary' | 'letters'): void {
    if (previewMode.value) {
        toast.info('Pratinjau ekspor siap diintegrasikan dengan backend.');

        return;
    }

    const baseUrl =
        kind === 'summary'
            ? props.routes?.export_summary
            : props.routes?.export_letters;

    if (!baseUrl) {
        toast.error('URL ekspor belum tersedia.');

        return;
    }

    const query = new URLSearchParams(
        Object.entries(activeFilters).filter(([, value]) => value !== ''),
    );

    window.location.assign(`${baseUrl}?${query.toString()}`);
}
</script>

<template>
    <Head title="Peta Penyelesaian Surat" />

    <main
        class="w-full max-w-full flex-1 overflow-x-hidden bg-muted/20 p-3 sm:p-5 lg:p-6"
    >
        <div class="mx-auto max-w-[112rem]">
            <PeriodicReportGraphWorkspace
                mode="AGGREGATE"
                :graph="graph"
                :summary="summary"
                :source-breakdown="sourceBreakdown"
                :intake-funnel="intakeFunnel"
                :sender-breakdown="senderBreakdown"
                :trend="trend"
                :letters="letters"
                :filters="activeFilters"
                :scope="scope"
                :can-export="previewMode || props.canExport === true"
                @change-filters="updateFilters"
                @reset-filters="resetFilters"
                @export="exportReport"
            />
        </div>
    </main>
</template>

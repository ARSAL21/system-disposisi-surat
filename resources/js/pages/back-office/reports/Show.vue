<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileWarning } from '@lucide/vue';
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
    previewReportProcessDetail,
} from '@/lib/periodicReportPreview';
import type {
    PaginatedPeriodicReportLetters,
    PeriodicReportDetailPageProps,
    PeriodicReportFilters as ReportFilters,
} from '@/types';

const props = defineProps<PeriodicReportDetailPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Laporan Periodik', href: '/back-office/reports' },
            { title: 'Jejak Keputusan', href: '#' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const report = computed(() =>
    previewMode.value ? previewReportProcessDetail : (props.report ?? null),
);
const initialFilters = computed<ReportFilters>(() =>
    previewMode.value
        ? previewPeriodicReportFilters
        : (props.filters ?? {
              date_from: '',
              date_to: '',
              source: '',
              event: 'RECEIVED',
              status: '',
              search: '',
          }),
);
const activeFilters = reactive<ReportFilters>({ ...initialFilters.value });
const graph = computed(() =>
    previewMode.value
        ? previewPeriodicReportOrganizationGraph
        : (props.organizationGraph ?? { generated_at: '', executives: [] }),
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
    if (previewMode.value) {
        return;
    }

    router.get(
        window.location.pathname,
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
    <Head
        :title="
            report ? `Jejak ${report.letter.agenda_number}` : 'Jejak Keputusan'
        "
    />

    <main
        class="w-full max-w-full flex-1 overflow-x-hidden bg-muted/20 p-3 sm:p-5 lg:p-6"
    >
        <div v-if="report" class="mx-auto max-w-[112rem]">
            <PeriodicReportGraphWorkspace
                mode="LETTER"
                :graph="graph"
                :report="report"
                :summary="summary"
                :source-breakdown="
                    previewMode
                        ? previewPeriodicReportSourceBreakdown
                        : (props.sourceBreakdown ?? [])
                "
                :intake-funnel="
                    previewMode
                        ? previewPeriodicReportIntakeFunnel
                        : (props.intakeFunnel ?? {
                              online_submissions: 0,
                              manual_submissions: 0,
                              converted_to_letters: 0,
                          })
                "
                :sender-breakdown="
                    previewMode
                        ? previewPeriodicReportSenderBreakdown
                        : (props.senderBreakdown ?? [])
                "
                :trend="
                    previewMode
                        ? previewPeriodicReportTrend
                        : (props.trend ?? [])
                "
                :letters="letters"
                :filters="activeFilters"
                :scope="
                    previewMode
                        ? previewPeriodicReportScope
                        : (props.scope ?? {
                              mode: 'SECTION_HEAD_BRANCHES',
                              label: 'Cakupan surat',
                              description: 'Cakupan sesuai jabatan aktif.',
                          })
                "
                :can-export="previewMode || props.canExport === true"
                @change-filters="updateFilters"
                @reset-filters="resetFilters"
                @export="exportReport"
            />
        </div>

        <section
            v-else
            class="mx-auto grid min-h-[60vh] max-w-2xl place-items-center text-center"
        >
            <div>
                <span
                    class="mx-auto flex size-16 items-center justify-center rounded-3xl bg-muted text-muted-foreground"
                >
                    <FileWarning class="size-7" />
                </span>
                <h1 class="mt-5 text-2xl font-semibold tracking-tight">
                    Data proses belum tersedia
                </h1>
                <p class="mt-3 text-sm leading-6 text-muted-foreground">
                    Backend tidak mengirimkan detail laporan untuk surat ini.
                    Data preview tidak digunakan pada URL produksi.
                </p>
            </div>
        </section>
    </main>
</template>

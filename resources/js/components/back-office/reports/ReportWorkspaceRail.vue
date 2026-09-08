<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight, X } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';
import ReportLetterExplorer from '@/components/back-office/reports/ReportLetterExplorer.vue';
import ReportNodeInspector from '@/components/back-office/reports/ReportNodeInspector.vue';
import ReportPeriodSummaryPanel from '@/components/back-office/reports/ReportPeriodSummaryPanel.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type {
    PaginatedPeriodicReportLetters,
    PeriodicReportIntakeFunnel,
    PeriodicReportSenderBreakdown,
    PeriodicReportSourceBreakdown,
    PeriodicReportSummary,
    PeriodicReportTrendPoint,
    ReportNodeInspectorData,
} from '@/types';

const props = defineProps<{
    open: boolean;
    view: 'SUMMARY' | 'LETTERS' | 'INSPECTOR';
    inspector: ReportNodeInspectorData | null;
    letters: PaginatedPeriodicReportLetters;
    selectedLetterReference?: string | null;
    summary: PeriodicReportSummary;
    trend: PeriodicReportTrendPoint[];
    sourceBreakdown: PeriodicReportSourceBreakdown[];
    intakeFunnel: PeriodicReportIntakeFunnel;
    senderBreakdown: PeriodicReportSenderBreakdown[];
}>();

const emit = defineEmits<{
    close: [];
    back: [];
}>();

const isDocked = useMediaQuery('(min-width: 1536px)');

const title = computed(() => {
    if (props.view === 'SUMMARY') {
        return 'Ringkasan periode';
    }

    if (props.view === 'LETTERS') {
        return 'Daftar surat';
    }

    return 'Detail cabang';
});

const relatedLetters = computed(() => {
    if (!props.inspector || props.inspector.context !== 'AGGREGATE') {
        return [];
    }

    const codes = new Set(props.inspector.participant_position_codes);

    return props.letters.data.filter((letter) =>
        letter.participant_position_codes.some((code) => codes.has(code)),
    );
});
</script>

<template>
    <aside
        v-if="isDocked && open"
        class="sticky top-4 flex h-[calc(100vh-2rem)] min-h-0 w-[24rem] shrink-0 flex-col overflow-hidden rounded-2xl border bg-background shadow-lg"
        aria-label="Panel informasi laporan"
        @keydown.esc="emit('close')"
    >
        <header class="flex items-center gap-2 border-b px-4 py-3">
            <Button
                v-if="view === 'INSPECTOR'"
                variant="ghost"
                size="icon"
                class="size-9"
                aria-label="Kembali ke daftar surat"
                @click="emit('back')"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <h2 class="min-w-0 flex-1 truncate text-sm font-semibold">
                {{ title }}
            </h2>
            <Button
                variant="ghost"
                size="icon"
                class="size-9"
                aria-label="Tutup panel"
                @click="emit('close')"
            >
                <X class="size-4" />
            </Button>
        </header>

        <ReportLetterExplorer
            v-if="view === 'LETTERS'"
            :letters="letters"
            :selected-reference="selectedLetterReference"
        />
        <div v-else class="min-h-0 flex-1 overflow-y-auto p-4">
            <ReportPeriodSummaryPanel
                v-if="view === 'SUMMARY'"
                :summary="summary"
                :trend="trend"
                :source-breakdown="sourceBreakdown"
                :intake-funnel="intakeFunnel"
                :sender-breakdown="senderBreakdown"
            />
            <template v-else-if="inspector">
                <ReportNodeInspector :inspector="inspector" />
                <section
                    v-if="relatedLetters.length"
                    class="mt-5 border-t pt-5"
                    aria-labelledby="related-letter-title"
                >
                    <h3 id="related-letter-title" class="text-sm font-semibold">
                        Surat pada posisi ini
                    </h3>
                    <ul class="mt-2 space-y-2">
                        <li
                            v-for="letter in relatedLetters"
                            :key="letter.reference"
                        >
                            <Link
                                :href="letter.links.detail"
                                class="flex items-center gap-2 rounded-xl border p-3 text-xs hover:border-indigo-300 hover:bg-indigo-50/40 focus-visible:ring-3 focus-visible:ring-indigo-500/35 focus-visible:outline-none dark:hover:bg-indigo-950/20"
                            >
                                <span class="min-w-0 flex-1">
                                    <strong
                                        class="block text-indigo-600 dark:text-indigo-300"
                                    >
                                        {{ letter.agenda_number }}
                                    </strong>
                                    <span
                                        class="mt-1 line-clamp-2 text-muted-foreground"
                                    >
                                        {{ letter.subject }}
                                    </span>
                                </span>
                                <ChevronRight class="size-4 shrink-0" />
                            </Link>
                        </li>
                    </ul>
                </section>
            </template>
        </div>
    </aside>

    <Sheet
        v-else-if="!isDocked"
        :open="open"
        @update:open="!$event && emit('close')"
    >
        <SheetContent
            class="w-[min(92vw,30rem)] gap-0 p-0 sm:max-w-[30rem]"
            overlay-class="bg-transparent"
        >
            <SheetHeader class="border-b p-4 pr-12 text-left">
                <div class="flex items-center gap-2">
                    <Button
                        v-if="view === 'INSPECTOR'"
                        variant="ghost"
                        size="icon"
                        class="size-9"
                        aria-label="Kembali ke daftar surat"
                        @click="emit('back')"
                    >
                        <ArrowLeft class="size-4" />
                    </Button>
                    <div>
                        <SheetTitle>{{ title }}</SheetTitle>
                        <SheetDescription>
                            Informasi laporan sesuai cakupan jabatan Anda.
                        </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <ReportLetterExplorer
                v-if="view === 'LETTERS'"
                :letters="letters"
                :selected-reference="selectedLetterReference"
            />
            <div v-else class="min-h-0 flex-1 overflow-y-auto p-4">
                <ReportPeriodSummaryPanel
                    v-if="view === 'SUMMARY'"
                    :summary="summary"
                    :trend="trend"
                    :source-breakdown="sourceBreakdown"
                    :intake-funnel="intakeFunnel"
                    :sender-breakdown="senderBreakdown"
                />
                <ReportNodeInspector
                    v-else-if="inspector"
                    :inspector="inspector"
                />
            </div>
        </SheetContent>
    </Sheet>
</template>

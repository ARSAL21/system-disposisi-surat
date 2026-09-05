<script setup lang="ts">
import { computed, ref } from 'vue';
import AggregateDecisionGraph from '@/components/back-office/reports/AggregateDecisionGraph.vue';
import LetterDecisionGraph from '@/components/back-office/reports/LetterDecisionGraph.vue';
import PeriodicReportFilters from '@/components/back-office/reports/PeriodicReportFilters.vue';
import ReportSummaryStrip from '@/components/back-office/reports/ReportSummaryStrip.vue';
import ReportWorkspaceHeader from '@/components/back-office/reports/ReportWorkspaceHeader.vue';
import ReportWorkspaceRail from '@/components/back-office/reports/ReportWorkspaceRail.vue';
import type {
    PaginatedPeriodicReportLetters,
    PeriodicReportFilters as ReportFilters,
    PeriodicReportIntakeFunnel,
    PeriodicReportOrganizationGraph,
    PeriodicReportScope,
    PeriodicReportSenderBreakdown,
    PeriodicReportSourceBreakdown,
    PeriodicReportSummary,
    PeriodicReportTrendPoint,
    ReportNodeInspectorData,
    ReportProcessDetail,
} from '@/types';

const props = defineProps<{
    mode: 'AGGREGATE' | 'LETTER';
    graph: PeriodicReportOrganizationGraph;
    report?: ReportProcessDetail | null;
    summary: PeriodicReportSummary;
    sourceBreakdown: PeriodicReportSourceBreakdown[];
    intakeFunnel: PeriodicReportIntakeFunnel;
    senderBreakdown: PeriodicReportSenderBreakdown[];
    trend: PeriodicReportTrendPoint[];
    letters: PaginatedPeriodicReportLetters;
    filters: ReportFilters;
    scope: PeriodicReportScope;
    canExport: boolean;
}>();

const emit = defineEmits<{
    changeFilters: [patch: Partial<ReportFilters>];
    resetFilters: [];
    export: [kind: 'summary' | 'letters'];
}>();

type RailView = 'SUMMARY' | 'LETTERS' | 'INSPECTOR';

const railOpen = ref(props.mode === 'LETTER');
const railView = ref<RailView>(props.mode === 'LETTER' ? 'LETTERS' : 'SUMMARY');
const returnView = ref<Exclude<RailView, 'INSPECTOR'>>('LETTERS');
const inspector = ref<ReportNodeInspectorData | null>(null);

const selectedReference = computed(() => inspector.value?.reference ?? null);

const attentionCount = computed(() => {
    if (props.mode === 'LETTER' && props.report) {
        return props.report.branches.reduce(
            (total, assistant) =>
                total +
                Number(assistant.attention.needs_attention) +
                assistant.children.filter(
                    (section) => section.attention.needs_attention,
                ).length,
            0,
        );
    }

    return props.graph.executives.reduce(
        (total, executive) =>
            total +
            Number(executive.attention.needs_attention) +
            executive.children.reduce(
                (assistantTotal, assistant) =>
                    assistantTotal +
                    Number(assistant.attention.needs_attention) +
                    assistant.children.filter(
                        (section) => section.attention.needs_attention,
                    ).length,
                0,
            ),
        0,
    );
});

function openRail(view: Exclude<RailView, 'INSPECTOR'>): void {
    railView.value = view;
    returnView.value = view;
    railOpen.value = true;
}

function inspectNode(data: ReportNodeInspectorData): void {
    if (railView.value !== 'INSPECTOR') {
        returnView.value = railView.value;
    }

    inspector.value = data;
    railView.value = 'INSPECTOR';
    railOpen.value = true;
}

function closeRail(): void {
    railOpen.value = false;
    inspector.value = null;
}

function backFromInspector(): void {
    inspector.value = null;
    railView.value = returnView.value;
}
</script>

<template>
    <div class="flex min-w-0 items-start gap-4">
        <div class="min-w-0 flex-1 space-y-4">
            <ReportWorkspaceHeader
                :mode="mode"
                :scope="scope"
                :filters="filters"
                :agenda-number="report?.letter.agenda_number"
                :can-export="canExport"
                @open-summary="openRail('SUMMARY')"
                @open-letters="openRail('LETTERS')"
                @export="emit('export', $event)"
            />

            <PeriodicReportFilters
                :filters="filters"
                @change="emit('changeFilters', $event)"
                @reset="emit('resetFilters')"
            />

            <ReportSummaryStrip
                :summary="summary"
                :attention-count="attentionCount"
            />

            <AggregateDecisionGraph
                v-if="mode === 'AGGREGATE'"
                :graph="graph"
                :selected-reference="selectedReference"
                @select-node="inspectNode"
            />
            <LetterDecisionGraph
                v-else-if="report"
                :report="report"
                :selected-reference="selectedReference"
                @select-node="inspectNode"
            />
        </div>

        <ReportWorkspaceRail
            :open="railOpen"
            :view="railView"
            :inspector="inspector"
            :letters="letters"
            :selected-letter-reference="report?.letter.reference"
            :summary="summary"
            :trend="trend"
            :source-breakdown="sourceBreakdown"
            :intake-funnel="intakeFunnel"
            :sender-breakdown="senderBreakdown"
            @close="closeRail"
            @back="backFromInspector"
        />
    </div>
</template>

<script setup lang="ts">
import { ArrowDown, ArrowRight, GitFork, Network } from '@lucide/vue';
import ReportGraphLegend from '@/components/back-office/reports/ReportGraphLegend.vue';
import ReportGraphNode from '@/components/back-office/reports/ReportGraphNode.vue';
import type {
    PeriodicReportOrganizationGraph,
    ReportAggregateAssistantNode,
    ReportAggregateExecutiveNode,
    ReportAggregateGraphNode,
    ReportNodeInspectorData,
} from '@/types';

const props = defineProps<{
    graph: PeriodicReportOrganizationGraph;
    selectedReference?: string | null;
}>();

const emit = defineEmits<{
    selectNode: [inspector: ReportNodeInspectorData];
}>();

function inspectorFor(
    level: ReportNodeInspectorData['level'],
    node: ReportAggregateGraphNode,
): ReportNodeInspectorData {
    return {
        reference: node.reference,
        context: 'AGGREGATE',
        level,
        position: node.recipient_position,
        status: null,
        progress: node.progress,
        attention: node.attention,
        timings: [
            { label: 'Aktivitas terakhir', value: node.last_activity_at },
        ],
        instructions: [],
        instruction_note: null,
        decided_by: null,
        decided_at: null,
        follow_ups: [],
        completion_note: null,
        completed_by: null,
        participant_position_codes: [node.recipient_position.code],
    };
}

function executivePathActive(executive: ReportAggregateExecutiveNode): boolean {
    return (
        executive.reference === props.selectedReference ||
        executive.children.some(
            (assistant) =>
                assistant.reference === props.selectedReference ||
                assistant.children.some(
                    (head) => head.reference === props.selectedReference,
                ),
        )
    );
}

function assistantPathActive(assistant: ReportAggregateAssistantNode): boolean {
    return (
        assistant.reference === props.selectedReference ||
        assistant.children.some(
            (head) => head.reference === props.selectedReference,
        )
    );
}
</script>

<template>
    <section
        class="min-w-0 overflow-hidden rounded-2xl border bg-muted/15 shadow-sm"
        aria-labelledby="aggregate-graph-title"
    >
        <header
            class="flex flex-col gap-4 border-b bg-background px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        >
            <div>
                <p
                    class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-300"
                >
                    <Network class="size-4" /> Peta organisasi agregat
                </p>
                <h2
                    id="aggregate-graph-title"
                    class="mt-1 text-lg font-semibold tracking-tight"
                >
                    Alur disposisi pada periode terpilih
                </h2>
            </div>
            <ReportGraphLegend />
        </header>

        <div v-if="graph.executives.length > 0" class="space-y-4 p-3 sm:p-4">
            <article
                v-for="executive in graph.executives"
                :key="executive.reference"
                class="rounded-2xl border bg-background p-3 sm:p-4"
            >
                <div
                    class="mb-3 flex items-center justify-between gap-3 border-b pb-3"
                >
                    <p class="text-xs font-semibold text-muted-foreground">
                        Jalur {{ executive.recipient_position.name }}
                    </p>
                    <p class="text-[10px] text-muted-foreground tabular-nums">
                        {{ executive.children.length }} Asisten terlibat
                    </p>
                </div>

                <div
                    class="grid items-center gap-3 xl:grid-cols-[12.5rem_2rem_minmax(0,1fr)]"
                    role="tree"
                    :aria-label="`Peta disposisi ${executive.recipient_position.name}`"
                >
                    <div role="treeitem" :aria-expanded="true">
                        <ReportGraphNode
                            level="EXECUTIVE_ENTRY"
                            :position="executive.recipient_position"
                            :progress="executive.progress"
                            :attention="executive.attention"
                            :timings="[
                                {
                                    label: 'Aktivitas terakhir',
                                    value: executive.last_activity_at,
                                },
                            ]"
                            :selected="
                                selectedReference === executive.reference
                            "
                            :path-active="executivePathActive(executive)"
                            @select="
                                emit(
                                    'selectNode',
                                    inspectorFor('EXECUTIVE_ENTRY', executive),
                                )
                            "
                        />
                    </div>

                    <div
                        class="flex items-center justify-center text-indigo-400"
                        aria-hidden="true"
                    >
                        <ArrowDown class="size-5 xl:hidden" />
                        <GitFork class="hidden size-5 xl:block" />
                    </div>

                    <ol class="space-y-3" role="group">
                        <li
                            v-for="assistant in executive.children"
                            :key="assistant.reference"
                            class="grid items-center gap-3 rounded-xl border border-dashed border-indigo-200/80 bg-indigo-50/25 p-2.5 xl:grid-cols-[13rem_2rem_minmax(0,1fr)] dark:border-indigo-900/70 dark:bg-indigo-950/10"
                            role="treeitem"
                            :aria-expanded="true"
                        >
                            <ReportGraphNode
                                level="ASSISTANT"
                                :position="assistant.recipient_position"
                                :progress="assistant.progress"
                                :attention="assistant.attention"
                                :timings="[
                                    {
                                        label: 'Aktivitas terakhir',
                                        value: assistant.last_activity_at,
                                    },
                                ]"
                                :selected="
                                    selectedReference === assistant.reference
                                "
                                :path-active="assistantPathActive(assistant)"
                                @select="
                                    emit(
                                        'selectNode',
                                        inspectorFor('ASSISTANT', assistant),
                                    )
                                "
                            />

                            <div
                                class="flex items-center justify-center text-teal-500"
                                aria-hidden="true"
                            >
                                <ArrowDown class="size-4 xl:hidden" />
                                <ArrowRight class="hidden size-4 xl:block" />
                            </div>

                            <ol class="grid gap-2 sm:grid-cols-2" role="group">
                                <li
                                    v-for="head in assistant.children"
                                    :key="head.reference"
                                    role="treeitem"
                                >
                                    <ReportGraphNode
                                        level="SECTION_HEAD"
                                        :position="head.recipient_position"
                                        :progress="head.progress"
                                        :attention="head.attention"
                                        :timings="[
                                            {
                                                label: 'Aktivitas terakhir',
                                                value: head.last_activity_at,
                                            },
                                        ]"
                                        :selected="
                                            selectedReference === head.reference
                                        "
                                        @select="
                                            emit(
                                                'selectNode',
                                                inspectorFor(
                                                    'SECTION_HEAD',
                                                    head,
                                                ),
                                            )
                                        "
                                    />
                                </li>
                            </ol>
                        </li>
                    </ol>
                </div>
            </article>
        </div>

        <div v-else class="grid min-h-80 place-items-center p-8 text-center">
            <div>
                <Network class="mx-auto size-8 text-muted-foreground" />
                <h3 class="mt-3 font-semibold">
                    Belum ada alur pada periode ini
                </h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Ubah periode untuk melihat peta disposisi lainnya.
                </p>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Network } from '@lucide/vue';
import { computed } from 'vue';
import ReportGraphLegend from '@/components/back-office/reports/ReportGraphLegend.vue';
import ReportGraphNode from '@/components/back-office/reports/ReportGraphNode.vue';
import type {
    PeriodicReportBranchProgress,
    ReportNodeInspectorData,
    ReportProcessAssistantBranch,
    ReportProcessDetail,
} from '@/types';

const props = defineProps<{
    report: ReportProcessDetail;
    selectedReference?: string | null;
}>();

const emit = defineEmits<{
    selectNode: [inspector: ReportNodeInspectorData];
}>();

const rootStatus = computed(() =>
    props.report.letter.status === 'COMPLETED' ? 'COMPLETED' : 'IN_PROGRESS',
);

function progressForAssistant(
    branch: ReportProcessAssistantBranch,
): PeriodicReportBranchProgress {
    const pending = branch.children.filter(
        (child) => child.status === 'PENDING',
    ).length;
    const inProgress = branch.children.filter(
        (child) => child.status === 'IN_PROGRESS',
    ).length;
    const completed = branch.children.filter(
        (child) => child.status === 'COMPLETED',
    ).length;
    const total = branch.children.length;

    return {
        total,
        pending,
        in_progress: inProgress,
        completed,
        percent_complete: total > 0 ? Math.round((completed / total) * 100) : 0,
    };
}

function rootInspector(): ReportNodeInspectorData {
    const route = props.report.initial_route;

    if (!route) {
        throw new Error('Surat belum memiliki routing awal.');
    }

    return {
        reference: props.report.letter.reference,
        context: 'LETTER',
        level: 'EXECUTIVE_ENTRY',
        position: route.target_position,
        status: rootStatus.value,
        progress: props.report.progress,
        attention: null,
        timings: [
            { label: 'Surat diterima', value: props.report.letter.received_at },
            { label: 'Diarahkan', value: route.routed_at },
            { label: 'Selesai', value: props.report.letter.completed_at },
        ],
        instructions: [],
        instruction_note: null,
        decided_by: route.routed_by,
        decided_at: route.routed_at,
        follow_ups: [],
        completion_note: null,
        completed_by: null,
        participant_position_codes: [route.target_position.code],
    };
}

function assistantInspector(
    branch: ReportProcessAssistantBranch,
): ReportNodeInspectorData {
    return {
        reference: branch.reference,
        context: 'LETTER',
        level: 'ASSISTANT',
        position: branch.recipient_position,
        status: branch.status,
        progress: progressForAssistant(branch),
        attention: branch.attention,
        timings: [
            { label: 'Diterima', value: branch.received_at },
            { label: 'Diteruskan', value: branch.forwarded_at },
        ],
        instructions: branch.instructions,
        instruction_note: branch.instruction_note,
        decided_by: branch.disposed_by,
        decided_at: branch.disposed_at,
        follow_ups: [],
        completion_note: null,
        completed_by: null,
        participant_position_codes: [branch.recipient_position.code],
    };
}

function sectionInspector(
    branch: ReportProcessAssistantBranch['children'][number],
): ReportNodeInspectorData {
    return {
        reference: branch.reference,
        context: 'LETTER',
        level: 'SECTION_HEAD',
        position: branch.recipient_position,
        status: branch.status,
        progress: null,
        attention: branch.attention,
        timings: [
            { label: 'Diterima', value: branch.received_at },
            { label: 'Mulai dikerjakan', value: branch.started_at },
            { label: 'Selesai', value: branch.completed_at },
        ],
        instructions: branch.instructions,
        instruction_note: branch.instruction_note,
        decided_by: branch.disposed_by,
        decided_at: branch.disposed_at,
        follow_ups: branch.follow_ups,
        completion_note: branch.completion_note,
        completed_by: branch.completed_by,
        participant_position_codes: [branch.recipient_position.code],
    };
}

function assistantPathActive(branch: ReportProcessAssistantBranch): boolean {
    return (
        branch.reference === props.selectedReference ||
        branch.children.some(
            (child) => child.reference === props.selectedReference,
        )
    );
}

const assistantGridClass = computed(() => {
    if (props.report.branches.length === 1) {
        return 'max-w-md grid-cols-1';
    }

    if (props.report.branches.length === 2) {
        return 'max-w-5xl md:grid-cols-2';
    }

    return 'max-w-[90rem] md:grid-cols-2 lg:grid-cols-3';
});

const assistantBusClass = computed(() =>
    props.report.branches.length === 2
        ? 'left-1/4 right-1/4'
        : 'left-[16.666%] right-[16.666%]',
);
</script>

<template>
    <section
        class="min-w-0 overflow-hidden rounded-2xl border bg-muted/15 shadow-sm"
        aria-labelledby="letter-graph-title"
    >
        <header
            class="flex flex-col gap-4 border-b bg-background px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-5"
        >
            <div class="min-w-0">
                <p
                    class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-300"
                >
                    <Network class="size-4" /> Pohon keputusan surat
                </p>
                <h2
                    id="letter-graph-title"
                    class="mt-1 text-lg font-semibold tracking-tight"
                >
                    {{ report.letter.agenda_number }}
                </h2>
                <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                    {{ report.letter.subject }} ·
                    {{ report.letter.sender_organization_name }}
                </p>
            </div>
            <ReportGraphLegend />
        </header>

        <div class="overflow-x-auto p-4 sm:p-6 lg:p-8">
            <div
                class="mx-auto min-w-0"
                role="tree"
                :aria-label="`Pohon keputusan surat ${report.letter.agenda_number}`"
            >
                <div
                    v-if="!report.initial_route"
                    class="mx-auto max-w-lg rounded-2xl border border-dashed bg-background p-8 text-center"
                >
                    <span
                        class="mx-auto grid size-12 place-items-center rounded-2xl bg-amber-500/10 text-amber-600"
                    >
                        <Network class="size-5" />
                    </span>
                    <h3 class="mt-4 font-semibold">Surat belum diarahkan</h3>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Surat masih berada pada tahap registrasi Bagian Umum dan
                        belum memiliki pohon disposisi.
                    </p>
                </div>

                <template v-else>
                    <div
                        class="mx-auto max-w-md"
                        role="treeitem"
                        :aria-expanded="true"
                    >
                        <ReportGraphNode
                            level="EXECUTIVE_ENTRY"
                            :position="report.initial_route.target_position"
                            :progress="report.progress"
                            :status="rootStatus"
                            :timings="[
                                {
                                    label: 'Diarahkan',
                                    value: report.initial_route.routed_at,
                                },
                            ]"
                            :selected="
                                selectedReference === report.letter.reference
                            "
                            :path-active="Boolean(selectedReference)"
                            @select="emit('selectNode', rootInspector())"
                        />
                    </div>

                    <div
                        v-if="report.branches.length"
                        class="relative mx-auto h-10 w-px bg-indigo-300 dark:bg-indigo-700"
                        aria-hidden="true"
                    >
                        <span
                            class="absolute bottom-0 left-1/2 size-3 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-indigo-500 bg-background ring-4 ring-indigo-100 dark:ring-indigo-950"
                        />
                    </div>

                    <ol
                        v-if="report.branches.length"
                        class="relative mx-auto grid gap-10 pt-8"
                        :class="assistantGridClass"
                        role="group"
                    >
                        <li
                            v-if="report.branches.length > 1"
                            class="absolute top-0 hidden h-px bg-indigo-300 md:block dark:bg-indigo-700"
                            :class="assistantBusClass"
                            aria-hidden="true"
                        />
                        <li
                            v-for="assistant in report.branches"
                            :key="assistant.reference"
                            class="relative min-w-0"
                            role="treeitem"
                            :aria-expanded="true"
                        >
                            <div
                                class="absolute -top-8 left-1/2 h-8 w-px -translate-x-1/2 bg-indigo-300 dark:bg-indigo-700"
                                aria-hidden="true"
                            >
                                <span
                                    class="absolute bottom-0 left-1/2 size-2.5 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-indigo-500 bg-background"
                                />
                            </div>

                            <div
                                class="rounded-2xl border border-indigo-200/80 bg-indigo-50/25 p-3 dark:border-indigo-900/70 dark:bg-indigo-950/10"
                            >
                                <p
                                    class="mb-2 text-center text-[10px] font-bold tracking-[0.14em] text-indigo-600 uppercase dark:text-indigo-300"
                                >
                                    Cabang Asisten
                                </p>
                                <ReportGraphNode
                                    level="ASSISTANT"
                                    :position="assistant.recipient_position"
                                    :progress="progressForAssistant(assistant)"
                                    :status="assistant.status"
                                    :attention="assistant.attention"
                                    :timings="[
                                        {
                                            label: 'Diteruskan',
                                            value: assistant.forwarded_at,
                                        },
                                    ]"
                                    :selected="
                                        selectedReference ===
                                        assistant.reference
                                    "
                                    :path-active="
                                        assistantPathActive(assistant)
                                    "
                                    @select="
                                        emit(
                                            'selectNode',
                                            assistantInspector(assistant),
                                        )
                                    "
                                />
                            </div>

                            <div
                                v-if="assistant.children.length"
                                class="relative mx-auto h-10 w-px bg-teal-300 dark:bg-teal-700"
                                aria-hidden="true"
                            >
                                <span
                                    class="absolute bottom-0 left-1/2 size-3 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-teal-500 bg-background ring-4 ring-teal-100 dark:ring-teal-950"
                                />
                            </div>

                            <ol
                                v-if="assistant.children.length"
                                class="relative grid gap-5 pt-7 sm:grid-cols-2"
                                role="group"
                            >
                                <li
                                    v-if="assistant.children.length > 1"
                                    class="absolute top-0 right-1/4 left-1/4 hidden h-px bg-teal-300 sm:block dark:bg-teal-700"
                                    aria-hidden="true"
                                />
                                <li
                                    v-for="section in assistant.children"
                                    :key="section.reference"
                                    class="relative min-w-0"
                                    role="treeitem"
                                >
                                    <div
                                        class="absolute -top-7 left-1/2 h-7 w-px -translate-x-1/2 bg-teal-300 dark:bg-teal-700"
                                        aria-hidden="true"
                                    >
                                        <span
                                            class="absolute bottom-0 left-1/2 size-2.5 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-teal-500 bg-background"
                                        />
                                    </div>
                                    <ReportGraphNode
                                        level="SECTION_HEAD"
                                        :position="section.recipient_position"
                                        :status="section.status"
                                        :attention="section.attention"
                                        :timings="[
                                            {
                                                label: 'Aktivitas',
                                                value:
                                                    section.completed_at ??
                                                    section.started_at ??
                                                    section.received_at,
                                            },
                                        ]"
                                        :selected="
                                            selectedReference ===
                                            section.reference
                                        "
                                        @select="
                                            emit(
                                                'selectNode',
                                                sectionInspector(section),
                                            )
                                        "
                                    />
                                </li>
                            </ol>

                            <div
                                v-else
                                class="mt-3 rounded-xl border border-dashed p-4 text-center text-xs text-muted-foreground"
                            >
                                Belum diteruskan ke Kepala Bagian.
                            </div>
                        </li>
                    </ol>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                    >
                        Surat belum memiliki cabang disposisi lanjutan.
                    </div>
                </template>
            </div>
        </div>
    </section>
</template>

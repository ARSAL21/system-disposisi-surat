<script setup lang="ts">
import { Network } from '@lucide/vue';
import AggregateGraphTreeNode from '@/components/back-office/reports/AggregateGraphTreeNode.vue';
import ReportGraphLegend from '@/components/back-office/reports/ReportGraphLegend.vue';
import type {
    PeriodicReportOrganizationGraph,
    ReportNodeInspectorData,
} from '@/types';

defineProps<{
    graph: PeriodicReportOrganizationGraph;
    selectedReference?: string | null;
}>();

const emit = defineEmits<{
    selectNode: [inspector: ReportNodeInspectorData];
}>();
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
                <p class="mt-1 text-sm text-muted-foreground">
                    Garis bertingkat menunjukkan alur jabatan yang benar,
                    termasuk arahan Wali Kota kepada Sekda sebelum cabang
                    Asisten dimulai.
                </p>
            </div>
            <ReportGraphLegend />
        </header>

        <ol
            v-if="graph.executives.length"
            class="grid gap-4 p-4 sm:p-5 lg:grid-cols-2"
            role="tree"
            aria-label="Peta struktur disposisi periode terpilih"
        >
            <AggregateGraphTreeNode
                v-for="executive in graph.executives"
                :key="executive.reference"
                :node="executive"
                :selected-reference="selectedReference"
                @select="emit('selectNode', $event)"
            />
        </ol>

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

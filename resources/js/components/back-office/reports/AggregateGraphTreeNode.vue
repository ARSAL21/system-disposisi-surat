<script setup lang="ts">
import { computed } from 'vue';
import ReportGraphNode from '@/components/back-office/reports/ReportGraphNode.vue';
import type {
    ReportAggregateGraphNode,
    ReportNodeInspectorData,
} from '@/types';

defineOptions({ name: 'AggregateGraphTreeNode' });

const props = defineProps<{
    node: ReportAggregateGraphNode;
    selectedReference?: string | null;
}>();

const emit = defineEmits<{
    select: [inspector: ReportNodeInspectorData];
}>();

const isPathActive = computed(
    () =>
        props.node.reference === props.selectedReference ||
        props.node.children.some((child) =>
            containsReference(child, props.selectedReference),
        ),
);

function containsReference(
    node: ReportAggregateGraphNode,
    reference?: string | null,
): boolean {
    return (
        node.reference === reference ||
        node.children.some((child) => containsReference(child, reference))
    );
}

function inspector(): ReportNodeInspectorData {
    return {
        reference: props.node.reference,
        context: 'AGGREGATE',
        level: props.node.level,
        position: props.node.recipient_position,
        status: null,
        progress: props.node.progress,
        attention: props.node.attention,
        timings: [
            {
                label: 'Aktivitas terakhir',
                value: props.node.last_activity_at,
            },
        ],
        instructions: [],
        instruction_note: null,
        decided_by: null,
        decided_at: null,
        follow_ups: [],
        completion_note: null,
        completed_by: null,
        participant_position_codes: [props.node.recipient_position.code],
    };
}
</script>

<template>
    <li
        class="relative min-w-0"
        role="treeitem"
        :aria-expanded="node.children.length > 0"
    >
        <ReportGraphNode
            :level="node.level"
            :position="node.recipient_position"
            :progress="node.progress"
            :attention="node.attention"
            :timings="[
                {
                    label: 'Aktivitas terakhir',
                    value: node.last_activity_at,
                },
            ]"
            :selected="selectedReference === node.reference"
            :path-active="isPathActive"
            @select="emit('select', inspector())"
        />

        <ol
            v-if="node.children.length"
            class="relative mt-3 ml-5 grid gap-3 border-l-2 border-indigo-200 pl-5 dark:border-indigo-900"
            role="group"
        >
            <AggregateGraphTreeNode
                v-for="child in node.children"
                :key="child.reference"
                :node="child"
                :selected-reference="selectedReference"
                @select="emit('select', $event)"
            />
        </ol>
    </li>
</template>

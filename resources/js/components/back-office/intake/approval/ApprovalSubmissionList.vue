<script setup lang="ts">
import ApprovalEmptyState from '@/components/back-office/intake/approval/ApprovalEmptyState.vue';
import ApprovalQueueCards from '@/components/back-office/intake/approval/ApprovalQueueCards.vue';
import ApprovalQueueTable from '@/components/back-office/intake/approval/ApprovalQueueTable.vue';
import type { ApprovalQueueTab, ApprovalSubmission } from '@/types';

defineProps<{
    submissions: ApprovalSubmission[];
    tab: ApprovalQueueTab;
}>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <ApprovalEmptyState
            v-if="submissions.length === 0"
            :history="tab === 'history'"
            @reset="$emit('reset')"
        />
        <template v-else>
            <ApprovalQueueTable :submissions="submissions" />
            <ApprovalQueueCards :submissions="submissions" />
            <slot name="pagination" />
        </template>
    </section>
</template>

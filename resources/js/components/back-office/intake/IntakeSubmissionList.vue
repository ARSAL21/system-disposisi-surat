<script setup lang="ts">
import IntakeEmptyState from '@/components/back-office/intake/IntakeEmptyState.vue';
import IntakeSubmissionCards from '@/components/back-office/intake/IntakeSubmissionCards.vue';
import IntakeSubmissionTable from '@/components/back-office/intake/IntakeSubmissionTable.vue';
import { Card } from '@/components/ui/card';
import type { IntakeSubmission } from '@/types';

defineProps<{ submissions: IntakeSubmission[] }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <Card class="overflow-hidden py-0 shadow-sm">
        <IntakeEmptyState
            v-if="submissions.length === 0"
            @reset="$emit('reset')"
        />
        <template v-else>
            <IntakeSubmissionTable :submissions="submissions" />
            <IntakeSubmissionCards :submissions="submissions" />
            <slot name="pagination" />
        </template>
    </Card>
</template>

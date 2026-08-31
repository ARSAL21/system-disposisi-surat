<script setup lang="ts">
import LetterActivityCards from '@/components/back-office/letter-activities/LetterActivityCards.vue';
import LetterActivityEmptyState from '@/components/back-office/letter-activities/LetterActivityEmptyState.vue';
import LetterActivityTable from '@/components/back-office/letter-activities/LetterActivityTable.vue';
import { Card } from '@/components/ui/card';
import type { LetterActivityRecord, LetterActivityVisibility } from '@/types';

defineProps<{
    activities: LetterActivityRecord[];
    filtered: boolean;
    timezone: string;
    visibility: LetterActivityVisibility;
}>();
defineEmits<{
    detail: [activity: LetterActivityRecord];
    reset: [];
}>();
</script>

<template>
    <Card class="overflow-hidden py-0 shadow-sm" aria-live="polite">
        <template v-if="activities.length">
            <LetterActivityTable
                :activities="activities"
                :timezone="timezone"
                :visibility="visibility"
                @detail="$emit('detail', $event)"
            />
            <LetterActivityCards
                :activities="activities"
                :timezone="timezone"
                :visibility="visibility"
                @detail="$emit('detail', $event)"
            />
        </template>
        <LetterActivityEmptyState
            v-else
            :filtered="filtered"
            @reset="$emit('reset')"
        />
        <slot name="pagination" />
    </Card>
</template>

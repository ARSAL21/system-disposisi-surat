<script setup lang="ts">
import { BadgeCheck, CircleX, RotateCcw } from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { approvalDecisionLabel } from '@/lib/intakeApprovalPresentation';
import { formatSubmissionDateTime } from '@/lib/submissionPresentation';
import type { ApprovalDecision } from '@/types';

const props = defineProps<{ decision: ApprovalDecision }>();

const presentation = computed(() => {
    if (props.decision.outcome === 'REJECTED') {
        return {
            icon: CircleX,
            card: 'border-rose-200 dark:border-rose-900',
            iconClass: 'text-rose-700 dark:text-rose-300',
        };
    }

    if (props.decision.outcome === 'INTERNAL_REVISION_REQUIRED') {
        return {
            icon: RotateCcw,
            card: 'border-amber-200 dark:border-amber-900',
            iconClass: 'text-amber-700 dark:text-amber-300',
        };
    }

    return {
        icon: BadgeCheck,
        card: 'border-emerald-200 dark:border-emerald-900',
        iconClass: 'text-emerald-700 dark:text-emerald-300',
    };
});
</script>

<template>
    <Card :class="['shadow-sm', presentation.card]">
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <component
                    :is="presentation.icon"
                    :class="['size-5', presentation.iconClass]"
                    aria-hidden="true"
                />
                {{ approvalDecisionLabel(decision.outcome) }}
            </CardTitle>
        </CardHeader>
        <CardContent>
            <p class="text-sm leading-6">
                {{ decision.note || 'Tidak ada catatan tambahan.' }}
            </p>
            <p class="mt-4 text-xs text-muted-foreground">
                {{ decision.decided_by }} &mdash;
                {{ formatSubmissionDateTime(decision.decided_at) }}
            </p>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { getIntakeStatusPresentation } from '@/lib/intakePresentation';
import type { IntakeSubmissionStatus } from '@/types';

defineProps<{ status: IntakeSubmissionStatus }>();
</script>

<template>
    <Badge
        variant="outline"
        :class="getIntakeStatusPresentation(status).badgeClass"
    >
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
        {{ getIntakeStatusPresentation(status).label }}
    </Badge>
</template>

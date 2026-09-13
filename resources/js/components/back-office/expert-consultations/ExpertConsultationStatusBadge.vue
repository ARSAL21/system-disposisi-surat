<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { ExpertConsultationStatus } from '@/types';

defineProps<{ status: ExpertConsultationStatus }>();
</script>

<template>
    <Badge
        variant="outline"
        :class="status === 'REPORTED'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
            : status === 'CANCELLED'
              ? 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
              : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300'"
    >
        {{ status === 'REPORTED' ? 'Sudah dilaporkan' : status === 'CANCELLED' ? 'Dibatalkan' : 'Menunggu telaah' }}
    </Badge>
</template>


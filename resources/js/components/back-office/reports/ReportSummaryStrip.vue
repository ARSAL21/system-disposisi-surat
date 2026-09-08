<script setup lang="ts">
import { AlertTriangle, CheckCircle2, Inbox, PlayCircle } from '@lucide/vue';
import type { Component } from 'vue';
import type { PeriodicReportSummary } from '@/types';

defineProps<{
    summary: PeriodicReportSummary;
    attentionCount: number;
}>();

const metrics: Array<{
    key: 'received_letters' | 'processing_started' | 'completed_letters';
    label: string;
    icon: Component;
    tone: string;
}> = [
    {
        key: 'received_letters',
        label: 'Surat diterima',
        icon: Inbox,
        tone: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300',
    },
    {
        key: 'processing_started',
        label: 'Mulai diproses',
        icon: PlayCircle,
        tone: 'bg-sky-500/10 text-sky-600 dark:text-sky-300',
    },
    {
        key: 'completed_letters',
        label: 'Surat selesai',
        icon: CheckCircle2,
        tone: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
    },
];
</script>

<template>
    <section
        class="grid gap-px overflow-hidden rounded-2xl border bg-border sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Ringkasan laporan"
    >
        <div
            v-for="metric in metrics"
            :key="metric.key"
            class="flex items-center gap-3 bg-background p-3.5"
        >
            <span
                class="grid size-9 shrink-0 place-items-center rounded-xl"
                :class="metric.tone"
            >
                <component :is="metric.icon" class="size-4" />
            </span>
            <div>
                <p class="text-xl font-semibold tabular-nums">
                    {{ summary[metric.key] }}
                </p>
                <p class="text-xs text-muted-foreground">{{ metric.label }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 bg-background p-3.5">
            <span
                class="grid size-9 shrink-0 place-items-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-300"
            >
                <AlertTriangle class="size-4" />
            </span>
            <div>
                <p class="text-xl font-semibold tabular-nums">
                    {{ attentionCount }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Posisi perlu perhatian
                </p>
            </div>
        </div>
    </section>
</template>

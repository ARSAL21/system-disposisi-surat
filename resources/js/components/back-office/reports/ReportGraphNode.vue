<script setup lang="ts">
import {
    AlertTriangle,
    CheckCircle2,
    CircleDotDashed,
    Clock3,
    Landmark,
    Play,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import { Badge } from '@/components/ui/badge';
import { formatRoutingDateTime } from '@/lib/letterRoutingPresentation';
import type {
    DispositionRecipientStatus,
    PeriodicReportBranchProgress,
    ReportGraphAttention,
    ReportInspectorTiming,
    ReportProcessPosition,
} from '@/types';

const props = defineProps<{
    level: 'MAYOR' | 'REGIONAL_SECRETARY' | 'ASSISTANT' | 'SECTION_HEAD';
    position: ReportProcessPosition;
    progress?: PeriodicReportBranchProgress | null;
    status?: DispositionRecipientStatus | null;
    attention?: ReportGraphAttention | null;
    timings?: ReportInspectorTiming[];
    selected?: boolean;
    pathActive?: boolean;
}>();

const emit = defineEmits<{ select: [] }>();

const levelLabel = computed(() => {
    if (props.level === 'MAYOR') {
        return 'Wali Kota';
    }

    if (props.level === 'REGIONAL_SECRETARY') {
        return 'Sekda';
    }

    return props.level === 'ASSISTANT' ? 'Asisten' : 'Kepala Bagian';
});

const levelIcon = computed<Component>(() =>
    props.level === 'MAYOR' || props.level === 'REGIONAL_SECRETARY'
        ? Landmark
        : UserRound,
);

const nodeTone = computed(() => {
    if (props.attention?.needs_attention) {
        return 'border-amber-400 bg-amber-50/80 shadow-amber-500/10 dark:border-amber-700 dark:bg-amber-950/25';
    }

    if (props.selected) {
        return 'border-indigo-500 bg-indigo-50/80 shadow-indigo-500/15 dark:border-indigo-500 dark:bg-indigo-950/30';
    }

    if (props.pathActive) {
        return 'border-indigo-300 bg-indigo-50/35 dark:border-indigo-800 dark:bg-indigo-950/15';
    }

    return 'border-border bg-card hover:border-indigo-300 hover:shadow-md dark:bg-slate-900/80 dark:hover:border-indigo-800';
});

const statusLabel = computed(() => {
    if (props.status === 'COMPLETED') {
        return 'Selesai';
    }

    if (props.status === 'IN_PROGRESS') {
        return 'Sedang dikerjakan';
    }

    return 'Menunggu tindakan';
});

const statusIcon = computed<Component>(() => {
    if (props.status === 'COMPLETED') {
        return CheckCircle2;
    }

    if (props.status === 'IN_PROGRESS') {
        return Play;
    }

    return CircleDotDashed;
});

const statusClass = computed(() => {
    if (props.status === 'COMPLETED') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/35 dark:text-emerald-300';
    }

    if (props.status === 'IN_PROGRESS') {
        return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/35 dark:text-sky-300';
    }

    return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/35 dark:text-amber-300';
});

const ariaLabel = computed(() => {
    const attention = props.attention?.needs_attention
        ? `, perlu perhatian: ${props.attention.reason}`
        : '';
    const progress = props.progress
        ? `, ${props.progress.completed} dari ${props.progress.total} selesai`
        : `, ${statusLabel.value}`;

    return `${levelLabel.value} ${props.position.name}, ${props.position.official_name ?? 'pejabat belum tersedia'}${progress}${attention}`;
});
</script>

<template>
    <button
        type="button"
        class="group/node relative w-full rounded-2xl border p-3.5 text-left shadow-sm transition-[border-color,box-shadow,transform,background-color] duration-200 hover:-translate-y-0.5 focus-visible:ring-3 focus-visible:ring-indigo-500/35 focus-visible:outline-none motion-reduce:transition-none"
        :class="nodeTone"
        :aria-label="ariaLabel"
        :aria-pressed="selected"
        @click="emit('select')"
    >
        <div class="flex items-start gap-3">
            <span
                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl"
                :class="
                    level === 'MAYOR' || level === 'REGIONAL_SECRETARY'
                        ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950'
                        : level === 'ASSISTANT'
                          ? 'bg-indigo-600 text-white'
                          : 'bg-teal-600 text-white'
                "
            >
                <component :is="levelIcon" class="size-4" aria-hidden="true" />
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <p
                        class="text-[10px] font-bold tracking-[0.13em] text-muted-foreground uppercase"
                    >
                        {{ levelLabel }}
                    </p>
                    <AlertTriangle
                        v-if="attention?.needs_attention"
                        class="size-4 shrink-0 text-amber-600 dark:text-amber-400"
                        aria-hidden="true"
                    />
                </div>
                <h3
                    class="mt-1 text-sm leading-5 font-semibold text-foreground"
                >
                    {{ position.name }}
                </h3>
                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{ position.official_name ?? 'Belum ada pejabat aktif' }}
                </p>
            </div>
        </div>

        <div v-if="progress" class="mt-3 border-t pt-3">
            <div class="flex items-center justify-between gap-3">
                <span class="text-[11px] text-muted-foreground"
                    >Progres cabang</span
                >
                <strong class="text-sm tabular-nums">
                    {{ progress.percent_complete }}%
                </strong>
            </div>
            <div
                class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-muted"
                role="img"
                :aria-label="`${progress.pending} menunggu, ${progress.in_progress} dikerjakan, ${progress.completed} selesai`"
            >
                <span
                    class="bg-amber-400"
                    :style="{
                        width: `${progress.total > 0 ? (progress.pending / progress.total) * 100 : 0}%`,
                    }"
                />
                <span
                    class="bg-sky-500"
                    :style="{
                        width: `${progress.total > 0 ? (progress.in_progress / progress.total) * 100 : 0}%`,
                    }"
                />
                <span
                    class="bg-emerald-500"
                    :style="{
                        width: `${progress.total > 0 ? (progress.completed / progress.total) * 100 : 0}%`,
                    }"
                />
            </div>
            <div class="mt-2 grid grid-cols-3 gap-1 text-center text-[10px]">
                <span
                    class="rounded-md bg-amber-500/10 py-1 text-amber-700 dark:text-amber-300"
                >
                    {{ progress.pending }} tunggu
                </span>
                <span
                    class="rounded-md bg-sky-500/10 py-1 text-sky-700 dark:text-sky-300"
                >
                    {{ progress.in_progress }} kerja
                </span>
                <span
                    class="rounded-md bg-emerald-500/10 py-1 text-emerald-700 dark:text-emerald-300"
                >
                    {{ progress.completed }} selesai
                </span>
            </div>
        </div>

        <div v-else-if="status" class="mt-3 border-t pt-3">
            <Badge variant="outline" :class="statusClass">
                <component :is="statusIcon" class="size-3" aria-hidden="true" />
                {{ statusLabel }}
            </Badge>
        </div>

        <dl v-if="timings?.length" class="mt-3 space-y-1.5 border-t pt-3">
            <div
                v-for="timing in timings"
                :key="timing.label"
                class="flex items-center justify-between gap-2 text-[10px]"
            >
                <dt
                    class="inline-flex items-center gap-1 text-muted-foreground"
                >
                    <Clock3 class="size-3" aria-hidden="true" />
                    {{ timing.label }}
                </dt>
                <dd class="text-right font-medium text-foreground tabular-nums">
                    {{ formatRoutingDateTime(timing.value) }}
                </dd>
            </div>
        </dl>

        <p
            v-if="attention?.needs_attention"
            class="mt-3 rounded-lg bg-amber-100 px-2.5 py-2 text-[10px] leading-4 font-medium text-amber-900 dark:bg-amber-950/70 dark:text-amber-200"
        >
            Tidak bergerak {{ attention.idle_hours ?? 48 }} jam. Klik untuk
            melihat penyebab.
        </p>
    </button>
</template>

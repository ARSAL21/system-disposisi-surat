<script setup lang="ts">
import {
    CheckCircle2,
    Clock3,
    FileCheck,
    GitBranch,
    Inbox,
    Route as RouteIcon,
    Send,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import type { ExecutiveInboxSummary, LetterRoutingSummary } from '@/types';

const props = defineProps<{
    mode: 'routing' | 'inbox';
    routingSummary?: LetterRoutingSummary;
    inboxSummary?: ExecutiveInboxSummary;
}>();

type SummaryEntry = {
    label: string;
    value: number;
    helper: string;
    icon: Component;
    gradient: string;
    iconColor: string;
    borderAccent: string;
    pulse: boolean;
};

const entries = computed<SummaryEntry[]>(() => {
    if (props.mode === 'inbox') {
        return [
            {
                label: 'Menunggu Disposisi',
                value: props.inboxSummary?.pending ?? 0,
                helper: 'Surat belum ditelaah',
                icon: Inbox,
                gradient: 'from-amber-500/15 via-amber-500/5 to-transparent',
                iconColor: 'text-amber-600 dark:text-amber-400 bg-amber-500/10',
                borderAccent: 'border-amber-500/30 hover:border-amber-500/60',
                pulse: (props.inboxSummary?.pending ?? 0) > 0,
            },
            {
                label: 'Menunggu Penerusan',
                value: props.inboxSummary?.awaiting_forwarding ?? 0,
                helper: 'Sedang di tingkat Asisten',
                icon: RouteIcon,
                gradient: 'from-indigo-500/15 via-indigo-500/5 to-transparent',
                iconColor:
                    'text-indigo-600 dark:text-indigo-400 bg-indigo-500/10',
                borderAccent: 'border-indigo-500/30 hover:border-indigo-500/60',
                pulse: false,
            },
            {
                label: 'Sedang Ditangani',
                value: props.inboxSummary?.in_progress ?? 0,
                helper: 'Cabang aktif berjalan',
                icon: GitBranch,
                gradient: 'from-sky-500/15 via-sky-500/5 to-transparent',
                iconColor: 'text-sky-600 dark:text-sky-400 bg-sky-500/10',
                borderAccent: 'border-sky-500/30 hover:border-sky-500/60',
                pulse: false,
            },
            {
                label: 'Disposisi Selesai',
                value: props.inboxSummary?.completed ?? 0,
                helper: 'Seluruh cabang tuntas',
                icon: CheckCircle2,
                gradient:
                    'from-emerald-500/15 via-emerald-500/5 to-transparent',
                iconColor:
                    'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10',
                borderAccent:
                    'border-emerald-500/30 hover:border-emerald-500/60',
                pulse: false,
            },
            {
                label: 'Diterima Hari Ini',
                value: props.inboxSummary?.received_today ?? 0,
                helper: 'Arus masuk hari kerja',
                icon: Clock3,
                gradient: 'from-purple-500/15 via-purple-500/5 to-transparent',
                iconColor:
                    'text-purple-600 dark:text-purple-400 bg-purple-500/10',
                borderAccent: 'border-purple-500/30 hover:border-purple-500/60',
                pulse: false,
            },
        ];
    }

    return [
        {
            label: 'Menunggu Routing',
            value: props.routingSummary?.awaiting_route ?? 0,
            helper: 'Surat berstatus teregistrasi',
            icon: RouteIcon,
            gradient: 'from-amber-500/15 via-amber-500/5 to-transparent',
            iconColor: 'text-amber-600 dark:text-amber-400 bg-amber-500/10',
            borderAccent: 'border-amber-500/30 hover:border-amber-500/60',
            pulse: (props.routingSummary?.awaiting_route ?? 0) > 0,
        },
        {
            label: 'Menunggu Pimpinan',
            value: props.routingSummary?.pending_executive ?? 0,
            helper: 'Initial route aktif di inbox',
            icon: FileCheck,
            gradient: 'from-indigo-500/15 via-indigo-500/5 to-transparent',
            iconColor: 'text-indigo-600 dark:text-indigo-400 bg-indigo-500/10',
            borderAccent: 'border-indigo-500/30 hover:border-indigo-500/60',
            pulse: false,
        },
        {
            label: 'Diarahkan Hari Ini',
            value: props.routingSummary?.routed_today ?? 0,
            helper: 'Selesai di routing',
            icon: Send,
            gradient: 'from-emerald-500/15 via-emerald-500/5 to-transparent',
            iconColor:
                'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10',
            borderAccent: 'border-emerald-500/30 hover:border-emerald-500/60',
            pulse: false,
        },
    ];
});
</script>

<template>
    <section
        :class="[
            'grid gap-3.5',
            mode === 'routing'
                ? 'grid-cols-1 sm:grid-cols-3'
                : 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-5',
        ]"
        :aria-label="
            mode === 'routing'
                ? 'Ringkasan antrean routing'
                : 'Ringkasan inbox pimpinan'
        "
    >
        <article
            v-for="entry in entries"
            :key="entry.label"
            class="group relative overflow-hidden rounded-2xl border border-border/70 bg-card p-4.5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-border/50 dark:bg-slate-900/80"
            :class="entry.borderAccent"
        >
            <!-- Background Subtle Gradient Wash -->
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br opacity-70 transition-opacity group-hover:opacity-100"
                :class="entry.gradient"
                aria-hidden="true"
            />

            <div class="relative flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <p
                        class="font-mono text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ entry.label }}
                    </p>
                    <p
                        class="font-['Syne',sans-serif] text-3xl font-extrabold text-foreground tabular-nums sm:text-4xl"
                    >
                        {{ entry.value }}
                    </p>
                    <p class="text-[11px] font-medium text-muted-foreground">
                        {{ entry.helper }}
                    </p>
                </div>

                <div
                    class="relative flex size-12 shrink-0 items-center justify-center rounded-2xl shadow-xs transition-transform duration-300 group-hover:scale-105"
                    :class="entry.iconColor"
                >
                    <component :is="entry.icon" class="size-6" />
                    <span
                        v-if="entry.pulse"
                        class="absolute -top-1 -right-1 size-3 animate-pulse rounded-full bg-amber-500 ring-4 ring-card"
                    />
                </div>
            </div>
        </article>
    </section>
</template>

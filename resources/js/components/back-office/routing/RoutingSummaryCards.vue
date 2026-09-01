<script setup lang="ts">
import { Clock3, Inbox, Route as RouteIcon, Send } from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
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
    tone: string;
};

const entries = computed<SummaryEntry[]>(() => {
    if (props.mode === 'inbox') {
        return [
            {
                label: 'Menunggu tindak lanjut',
                value: props.inboxSummary?.pending ?? 0,
                helper: 'Route aktif di inbox',
                icon: Inbox,
                tone: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
            },
            {
                label: 'Diterima hari ini',
                value: props.inboxSummary?.received_today ?? 0,
                helper: 'Berdasarkan waktu kantor',
                icon: Clock3,
                tone: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
            },
        ];
    }

    return [
        {
            label: 'Menunggu routing',
            value: props.routingSummary?.awaiting_route ?? 0,
            helper: 'Surat berstatus teregistrasi',
            icon: RouteIcon,
            tone: 'bg-amber-500/10 text-amber-800 dark:text-amber-300',
        },
        {
            label: 'Menunggu pimpinan',
            value: props.routingSummary?.pending_executive ?? 0,
            helper: 'Initial route masih aktif',
            icon: Inbox,
            tone: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
        },
        {
            label: 'Diarahkan hari ini',
            value: props.routingSummary?.routed_today ?? 0,
            helper: 'Berdasarkan waktu kantor',
            icon: Send,
            tone: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
        },
    ];
});
</script>

<template>
    <section
        :class="[
            'grid gap-3',
            mode === 'routing' ? 'sm:grid-cols-3' : 'sm:grid-cols-2',
        ]"
        :aria-label="
            mode === 'routing'
                ? 'Ringkasan antrean routing'
                : 'Ringkasan inbox pimpinan'
        "
    >
        <Card
            v-for="entry in entries"
            :key="entry.label"
            class="border-slate-200/80 py-0 shadow-none dark:border-slate-800"
        >
            <CardContent class="flex items-center gap-4 p-4 sm:p-5">
                <span
                    :class="[
                        'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                        entry.tone,
                    ]"
                >
                    <component
                        :is="entry.icon"
                        class="size-5"
                        aria-hidden="true"
                    />
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ entry.value }}
                    </p>
                    <p class="text-sm font-medium">{{ entry.label }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ entry.helper }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </section>
</template>

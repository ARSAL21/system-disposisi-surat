<script setup lang="ts">
import {
    CircleCheckBig,
    ClipboardCheck,
    Inbox,
    ListChecks,
    Undo2,
} from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';
import type { LetterActivitySummary } from '@/types';

defineProps<{
    summary: LetterActivitySummary;
}>();

const statistics = [
    {
        key: 'total' as const,
        label: 'Aktivitas tercatat',
        icon: ListChecks,
        class: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    },
    {
        key: 'received' as const,
        label: 'Pengajuan masuk',
        icon: Inbox,
        class: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    {
        key: 'awaiting_approval' as const,
        label: 'Siap ditinjau Kabag',
        icon: ClipboardCheck,
        class: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    },
    {
        key: 'registered' as const,
        label: 'Surat diregistrasi',
        icon: CircleCheckBig,
        class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
    {
        key: 'needs_follow_up' as const,
        label: 'Perlu tindak lanjut',
        icon: Undo2,
        class: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
];
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
        aria-label="Ringkasan aktivitas surat"
    >
        <Card
            v-for="statistic in statistics"
            :key="statistic.key"
            class="border-slate-200/80 py-0 shadow-none dark:border-slate-800"
        >
            <CardContent class="flex items-center gap-4 p-4 sm:p-5">
                <span
                    :class="[
                        'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                        statistic.class,
                    ]"
                >
                    <component
                        :is="statistic.icon"
                        class="size-5"
                        aria-hidden="true"
                    />
                </span>
                <div class="min-w-0">
                    <p
                        class="text-2xl font-semibold text-slate-950 tabular-nums dark:text-white"
                    >
                        {{ summary[statistic.key] }}
                    </p>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ statistic.label }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </section>
</template>

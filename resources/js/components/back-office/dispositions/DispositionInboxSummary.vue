<script setup lang="ts">
import { Clock3, Inbox, LoaderCircle } from '@lucide/vue';
import type { Component } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import type { DispositionInboxSummary } from '@/types';

const props = defineProps<{ summary: DispositionInboxSummary }>();

const entries: Array<{
    key: keyof DispositionInboxSummary;
    label: string;
    helper: string;
    icon: Component;
    tone: string;
}> = [
    {
        key: 'pending',
        label: 'Menunggu tindak lanjut',
        helper: 'Belum mulai ditangani',
        icon: Inbox,
        tone: 'bg-amber-500/10 text-amber-800 dark:text-amber-300',
    },
    {
        key: 'in_progress',
        label: 'Sedang ditangani',
        helper: 'Cabang aktif berjalan',
        icon: LoaderCircle,
        tone: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    {
        key: 'received_today',
        label: 'Diterima hari ini',
        helper: 'Berdasarkan waktu kantor',
        icon: Clock3,
        tone: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
];
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-3"
        aria-label="Ringkasan inbox disposisi"
    >
        <Card
            v-for="entry in entries"
            :key="entry.key"
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
                        {{ props.summary[entry.key] }}
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

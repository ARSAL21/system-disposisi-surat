<script setup lang="ts">
import { Archive, CalendarClock, FileClock, Layers3 } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';
import type { DocumentArchiveSummary } from '@/types';

defineProps<{ summary: DocumentArchiveSummary }>();

const items = [
    {
        key: 'total_letters' as const,
        label: 'Surat resmi',
        icon: Archive,
        class: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    {
        key: 'corrected_letters' as const,
        label: 'Memiliki koreksi',
        icon: FileClock,
        class: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
    {
        key: 'total_versions' as const,
        label: 'Total versi tersimpan',
        icon: Layers3,
        class: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    },
    {
        key: 'updated_this_month' as const,
        label: 'Diperbarui bulan ini',
        icon: CalendarClock,
        class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
];
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Ringkasan arsip dokumen"
    >
        <Card
            v-for="item in items"
            :key="item.key"
            class="border-slate-200/80 py-0 shadow-none dark:border-slate-800"
        >
            <CardContent class="flex items-center gap-4 p-4 sm:p-5">
                <span
                    :class="[
                        'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                        item.class,
                    ]"
                >
                    <component
                        :is="item.icon"
                        class="size-5"
                        aria-hidden="true"
                    />
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ summary[item.key] }}
                    </p>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ item.label }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </section>
</template>

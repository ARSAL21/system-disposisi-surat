<script setup lang="ts">
import { BadgeCheck, ClipboardList, RotateCcw, ScanSearch } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';
import type { IntakeSummary } from '@/types';

defineProps<{ summary: IntakeSummary }>();

const items = [
    {
        key: 'awaiting_screening' as const,
        label: 'Menunggu pemeriksaan',
        icon: ScanSearch,
        class: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    {
        key: 'returned_to_staff' as const,
        label: 'Dikembalikan ke petugas',
        icon: RotateCcw,
        class: 'bg-orange-500/10 text-orange-700 dark:text-orange-300',
    },
    {
        key: 'revision_required' as const,
        label: 'Perbaikan pengirim',
        icon: RotateCcw,
        class: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
    {
        key: 'ready_for_approval' as const,
        label: 'Menunggu Kabag',
        icon: ClipboardList,
        class: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    },
    {
        key: 'processed_today' as const,
        label: 'Diproses hari ini',
        icon: BadgeCheck,
        class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
];
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
        aria-label="Ringkasan antrean"
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
                <div>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ summary[item.key] }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ item.label }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </section>
</template>

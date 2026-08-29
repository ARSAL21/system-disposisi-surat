<script setup lang="ts">
import { BadgeCheck, CircleX, ClipboardClock, RotateCcw } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';
import type { ApprovalSummary } from '@/types';

defineProps<{ summary: ApprovalSummary }>();

const items = [
    {
        key: 'awaiting_decision' as const,
        label: 'Menunggu keputusan',
        icon: ClipboardClock,
        class: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    {
        key: 'returned_to_staff' as const,
        label: 'Kembali ke petugas',
        icon: RotateCcw,
        class: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
    {
        key: 'registered' as const,
        label: 'Terdaftar resmi',
        icon: BadgeCheck,
        class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
    {
        key: 'rejected' as const,
        label: 'Ditolak',
        icon: CircleX,
        class: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    },
];
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Ringkasan persetujuan surat"
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

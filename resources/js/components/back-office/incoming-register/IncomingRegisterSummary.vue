<script setup lang="ts">
import { BookMarked, CalendarCheck2, Globe2, ScanLine } from '@lucide/vue';
import type { Component } from 'vue';
import type { IncomingRegisterSummary } from '@/types';

const props = defineProps<{ summary: IncomingRegisterSummary }>();

const cards: Array<{
    key: keyof IncomingRegisterSummary;
    label: string;
    description: string;
    icon: Component;
    className: string;
}> = [
    {
        key: 'total_letters',
        label: 'Seluruh surat',
        description: 'Total register aktif',
        icon: BookMarked,
        className:
            'bg-slate-950 text-white dark:bg-slate-100 dark:text-slate-950',
    },
    {
        key: 'online_letters',
        label: 'Masuk online',
        description: 'Dikirim melalui portal',
        icon: Globe2,
        className:
            'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
    },
    {
        key: 'manual_letters',
        label: 'Surat fisik',
        description: 'Dicatat oleh Petugas',
        icon: ScanLine,
        className:
            'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    },
    {
        key: 'received_today',
        label: 'Diterima hari ini',
        description: 'Online dan manual',
        icon: CalendarCheck2,
        className:
            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    },
];
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Ringkasan buku surat masuk"
    >
        <article
            v-for="card in cards"
            :key="card.key"
            class="flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-xs"
        >
            <span
                :class="[
                    'flex size-11 shrink-0 items-center justify-center rounded-xl',
                    card.className,
                ]"
            >
                <component :is="card.icon" class="size-5" aria-hidden="true" />
            </span>
            <div class="min-w-0">
                <p class="text-2xl font-semibold tracking-tight tabular-nums">
                    {{ props.summary[card.key].toLocaleString('id-ID') }}
                </p>
                <p class="mt-0.5 text-sm font-semibold">{{ card.label }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{ card.description }}
                </p>
            </div>
        </article>
    </section>
</template>

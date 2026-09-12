<script setup lang="ts">
import { BadgeCheck, FileClock, Hash, Send, Waypoints } from '@lucide/vue';
import type { Component } from 'vue';
import type { OutgoingLetterSummary } from '@/types';

const props = defineProps<{ summary: OutgoingLetterSummary }>();

const cards: Array<{
    key: keyof OutgoingLetterSummary;
    label: string;
    icon: Component;
    tone: string;
}> = [
    {
        key: 'total',
        label: 'Total mandat',
        icon: Waypoints,
        tone: 'text-slate-600 bg-slate-500/10',
    },
    {
        key: 'awaiting_number',
        label: 'Butuh nomor',
        icon: Hash,
        tone: 'text-indigo-600 bg-indigo-500/10',
    },
    {
        key: 'awaiting_verification',
        label: 'Perlu verifikasi',
        icon: FileClock,
        tone: 'text-amber-600 bg-amber-500/10',
    },
    {
        key: 'ready_for_delivery',
        label: 'Siap dikirim',
        icon: BadgeCheck,
        tone: 'text-sky-600 bg-sky-500/10',
    },
    {
        key: 'delivered_this_month',
        label: 'Terkirim bulan ini',
        icon: Send,
        tone: 'text-emerald-600 bg-emerald-500/10',
    },
];
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
        aria-label="Ringkasan register surat keluar"
    >
        <article
            v-for="card in cards"
            :key="card.key"
            class="rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="flex items-center justify-between gap-3">
                <span
                    :class="[
                        'grid size-9 place-items-center rounded-xl',
                        card.tone,
                    ]"
                >
                    <component
                        :is="card.icon"
                        class="size-4"
                        aria-hidden="true"
                    />
                </span>
                <strong class="text-2xl font-semibold tabular-nums">{{
                    props.summary[card.key]
                }}</strong>
            </div>
            <p class="mt-3 text-xs font-medium text-muted-foreground">
                {{ card.label }}
            </p>
        </article>
    </section>
</template>

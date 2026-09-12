<script setup lang="ts">
import {
    BadgeCheck,
    Check,
    FileSignature,
    Gavel,
    Hash,
    Send,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import type { OutgoingLetterStatus } from '@/types';

const props = defineProps<{ status: OutgoingLetterStatus }>();

const responseStages: Array<{
    status: OutgoingLetterStatus;
    label: string;
    caption: string;
    icon: Component;
}> = [
    {
        status: 'AUTHORIZED',
        label: 'Mandat',
        caption: 'Substansi disetujui',
        icon: Gavel,
    },
    {
        status: 'NUMBER_ASSIGNED',
        label: 'Penomoran',
        caption: 'Nomor dan tanggal resmi',
        icon: Hash,
    },
    {
        status: 'SIGNED_DOCUMENT_UPLOADED',
        label: 'Tanda tangan',
        caption: 'PDF final diunggah',
        icon: FileSignature,
    },
    {
        status: 'ADMIN_VERIFIED',
        label: 'Verifikasi',
        caption: 'Diperiksa Kabag Umum',
        icon: BadgeCheck,
    },
    {
        status: 'DELIVERED',
        label: 'Pengiriman',
        caption: 'Bukti serah tercatat',
        icon: Send,
    },
];

const standaloneStages: Array<{
    status: OutgoingLetterStatus;
    label: string;
    caption: string;
    icon: Component;
}> = [
    {
        status: 'NUMBER_ASSIGNED',
        label: 'Penomoran',
        caption: 'Nomor dan tanggal resmi',
        icon: Hash,
    },
    {
        status: 'SEKDA_REVIEW',
        label: 'Pengesahan',
        caption: 'Keputusan Sekda',
        icon: Gavel,
    },
    {
        status: 'AWAITING_MANUAL_SIGNATURE',
        label: 'Tanda tangan',
        caption: 'Menunggu proses fisik',
        icon: FileSignature,
    },
    {
        status: 'MANUAL_SCAN_REVIEW',
        label: 'Pemeriksaan',
        caption: 'Scan diperiksa Kabag',
        icon: BadgeCheck,
    },
    {
        status: 'READY_FOR_DELIVERY',
        label: 'Siap kirim',
        caption: 'Pengesahan telah selesai',
        icon: Send,
    },
    {
        status: 'DELIVERED',
        label: 'Terkirim',
        caption: 'Bukti serah tercatat',
        icon: Send,
    },
];

const isStandaloneWorkflow = computed(() =>
    [
        'SEKDA_REVIEW',
        'AWAITING_MANUAL_SIGNATURE',
        'MANUAL_SCAN_REVIEW',
        'READY_FOR_DELIVERY',
        'REVISION_REQUIRED',
    ].includes(props.status),
);
const stages = computed(() =>
    isStandaloneWorkflow.value ? standaloneStages : responseStages,
);
const activeIndex = computed(() =>
    stages.value.findIndex((stage) => stage.status === props.status),
);
</script>

<template>
    <section
        class="rounded-3xl border bg-card p-5 shadow-sm sm:p-6"
        aria-labelledby="publication-progress-heading"
    >
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p
                    class="text-xs font-semibold tracking-[0.16em] text-indigo-600 uppercase dark:text-indigo-300"
                >
                    Alur penerbitan
                </p>
                <h2
                    id="publication-progress-heading"
                    class="mt-1 text-lg font-semibold"
                >
                    Posisi surat saat ini
                </h2>
            </div>
            <p class="text-xs text-muted-foreground">
                Setiap tahap dicatat oleh server dan tidak dapat dilompati.
            </p>
        </div>

        <div
            v-if="status === 'WITHDRAWN'"
            class="mt-5 flex items-start gap-3 rounded-2xl border border-slate-300 bg-slate-100 p-4 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
        >
            <span
                class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-700 text-white"
                ><X class="size-4"
            /></span>
            <div>
                <p class="font-semibold">Mandat dihentikan sebelum penomoran</p>
                <p class="mt-1 text-sm leading-6 opacity-80">
                    Riwayat tetap disimpan, tetapi surat ini tidak dapat
                    dilanjutkan ke penerbitan.
                </p>
            </div>
        </div>

        <ol v-else class="mt-6 grid gap-0 md:grid-cols-5">
            <li
                v-for="(stage, index) in stages"
                :key="stage.status"
                class="relative flex gap-4 pb-6 last:pb-0 md:block md:pb-0 md:text-center"
            >
                <span
                    v-if="index < stages.length - 1"
                    class="absolute top-10 bottom-0 left-5 w-0.5 md:top-5 md:right-0 md:bottom-auto md:left-1/2 md:h-0.5 md:w-full"
                    :class="
                        index < activeIndex ? 'bg-emerald-500' : 'bg-border'
                    "
                    aria-hidden="true"
                />
                <span
                    class="relative z-10 grid size-10 shrink-0 place-items-center rounded-full border-2 bg-background transition-colors"
                    :class="
                        index < activeIndex
                            ? 'border-emerald-500 bg-emerald-500 text-white'
                            : index === activeIndex
                              ? 'border-indigo-600 text-indigo-700 ring-4 ring-indigo-500/10 dark:text-indigo-300'
                              : 'border-border text-muted-foreground'
                    "
                >
                    <Check v-if="index < activeIndex" class="size-4" />
                    <component :is="stage.icon" v-else class="size-4" />
                </span>
                <div class="min-w-0 md:mt-3 md:px-2">
                    <p
                        class="text-sm font-semibold"
                        :class="
                            index === activeIndex
                                ? 'text-indigo-700 dark:text-indigo-300'
                                : ''
                        "
                    >
                        {{ stage.label }}
                    </p>
                    <p class="mt-1 text-xs leading-4 text-muted-foreground">
                        {{ stage.caption }}
                    </p>
                </div>
            </li>
        </ol>
    </section>
</template>

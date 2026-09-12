<script setup lang="ts">
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    FileClock,
    MessageSquareText,
    UserRoundCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { formatRoutingDateTime } from '@/lib/letterRoutingPresentation';
import type { ReportNodeInspectorData } from '@/types';

const props = defineProps<{ inspector: ReportNodeInspectorData }>();

const levelLabel = computed(() => {
    if (props.inspector.level === 'MAYOR') {
        return 'Wali Kota';
    }

    if (props.inspector.level === 'REGIONAL_SECRETARY') {
        return 'Sekda';
    }

    return props.inspector.level === 'ASSISTANT' ? 'Asisten' : 'Kepala Bagian';
});

const statusLabel = computed(() => {
    if (!props.inspector.status) {
        return 'Ringkasan periode';
    }

    if (props.inspector.status === 'COMPLETED') {
        return 'Selesai';
    }

    return props.inspector.status === 'IN_PROGRESS'
        ? 'Sedang dikerjakan'
        : 'Menunggu tindakan';
});
</script>

<template>
    <div class="space-y-5">
        <header>
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="secondary">{{ levelLabel }}</Badge>
                <Badge variant="outline">{{ statusLabel }}</Badge>
            </div>
            <h2 class="mt-3 text-xl leading-7 font-semibold tracking-tight">
                {{ inspector.position.name }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{
                    inspector.position.official_name ??
                    'Pejabat aktif belum tersedia'
                }}
            </p>
            <p
                v-if="inspector.position.unit_name"
                class="mt-1 text-xs text-muted-foreground"
            >
                {{ inspector.position.unit_name }}
            </p>
        </header>

        <section
            v-if="inspector.attention?.needs_attention"
            class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100"
            aria-label="Informasi yang perlu diperhatikan"
        >
            <p class="flex items-center gap-2 text-sm font-semibold">
                <AlertTriangle class="size-4" /> Perlu diperhatikan
            </p>
            <p class="mt-1 text-xs leading-5">
                {{ inspector.attention.reason }}
            </p>
            <p class="mt-1 text-[11px] opacity-75">
                Penanda ini berarti tidak ada aktivitas minimal 48 jam, bukan
                pelanggaran SLA.
            </p>
        </section>

        <section v-if="inspector.progress" class="rounded-xl border p-3.5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold text-muted-foreground">
                    Progres cabang
                </p>
                <strong class="text-xl tabular-nums">
                    {{ inspector.progress.percent_complete }}%
                </strong>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-emerald-500"
                    :style="{
                        width: `${inspector.progress.percent_complete}%`,
                    }"
                />
            </div>
            <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg bg-amber-500/10 p-2">
                    <dt class="text-[10px] text-muted-foreground">Menunggu</dt>
                    <dd class="mt-1 font-semibold tabular-nums">
                        {{ inspector.progress.pending }}
                    </dd>
                </div>
                <div class="rounded-lg bg-sky-500/10 p-2">
                    <dt class="text-[10px] text-muted-foreground">
                        Dikerjakan
                    </dt>
                    <dd class="mt-1 font-semibold tabular-nums">
                        {{ inspector.progress.in_progress }}
                    </dd>
                </div>
                <div class="rounded-lg bg-emerald-500/10 p-2">
                    <dt class="text-[10px] text-muted-foreground">Selesai</dt>
                    <dd class="mt-1 font-semibold tabular-nums">
                        {{ inspector.progress.completed }}
                    </dd>
                </div>
            </dl>
        </section>

        <section v-if="inspector.timings.length" aria-labelledby="timing-title">
            <h3
                id="timing-title"
                class="flex items-center gap-2 text-sm font-semibold"
            >
                <Clock3 class="size-4 text-indigo-500" /> Waktu proses
            </h3>
            <dl class="mt-2 divide-y rounded-xl border px-3">
                <div
                    v-for="timing in inspector.timings"
                    :key="timing.label"
                    class="flex items-center justify-between gap-3 py-2.5 text-xs"
                >
                    <dt class="text-muted-foreground">{{ timing.label }}</dt>
                    <dd class="text-right font-medium tabular-nums">
                        {{ formatRoutingDateTime(timing.value) }}
                    </dd>
                </div>
            </dl>
        </section>

        <section
            v-if="inspector.instructions.length || inspector.instruction_note"
            aria-labelledby="instruction-title"
        >
            <h3
                id="instruction-title"
                class="flex items-center gap-2 text-sm font-semibold"
            >
                <MessageSquareText class="size-4 text-indigo-500" /> Instruksi
            </h3>
            <div class="mt-2 rounded-xl border p-3">
                <div class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="instruction in inspector.instructions"
                        :key="instruction.code"
                        variant="secondary"
                    >
                        {{ instruction.name }}
                    </Badge>
                </div>
                <p
                    v-if="inspector.instruction_note"
                    class="mt-3 text-xs leading-5 text-muted-foreground"
                >
                    {{ inspector.instruction_note }}
                </p>
            </div>
        </section>

        <section v-if="inspector.decided_by" class="rounded-xl bg-muted/50 p-3">
            <p class="flex items-center gap-2 text-xs font-semibold">
                <UserRoundCheck class="size-4 text-indigo-500" /> Diteruskan
                oleh
            </p>
            <p class="mt-2 text-sm font-medium">
                {{ inspector.decided_by.name }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ inspector.decided_by.position }} ·
                {{ formatRoutingDateTime(inspector.decided_at) }}
            </p>
        </section>

        <section
            v-if="inspector.follow_ups.length"
            aria-labelledby="journal-title"
        >
            <h3
                id="journal-title"
                class="flex items-center gap-2 text-sm font-semibold"
            >
                <FileClock class="size-4 text-indigo-500" /> Jurnal tindak
                lanjut
            </h3>
            <ol class="mt-2 space-y-2 border-l-2 border-indigo-200 pl-4">
                <li
                    v-for="followUp in inspector.follow_ups"
                    :key="`${followUp.created_at}-${followUp.note}`"
                    class="relative rounded-xl border bg-background p-3 text-xs"
                >
                    <span
                        class="absolute top-4 -left-[1.34rem] size-2 rounded-full bg-indigo-500 ring-4 ring-background"
                        aria-hidden="true"
                    />
                    <p class="leading-5">{{ followUp.note }}</p>
                    <p class="mt-2 text-[11px] text-muted-foreground">
                        {{ followUp.created_by.name }} ·
                        {{ formatRoutingDateTime(followUp.created_at) }}
                    </p>
                </li>
            </ol>
        </section>

        <section
            v-if="inspector.completion_note"
            class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-900 dark:bg-emerald-950/25"
        >
            <p class="flex items-center gap-2 text-sm font-semibold">
                <CheckCircle2 class="size-4 text-emerald-600" /> Hasil
                penyelesaian
            </p>
            <p class="mt-2 text-xs leading-5">
                {{ inspector.completion_note }}
            </p>
            <p
                v-if="inspector.completed_by"
                class="mt-2 text-[11px] text-muted-foreground"
            >
                Diselesaikan oleh {{ inspector.completed_by.name }}
            </p>
        </section>
    </div>
</template>

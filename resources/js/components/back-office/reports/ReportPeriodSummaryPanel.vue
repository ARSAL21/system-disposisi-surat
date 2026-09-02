<script setup lang="ts">
import { BarChart3, Inbox, Radio, Send } from '@lucide/vue';
import { computed } from 'vue';
import type {
    PeriodicReportIntakeFunnel,
    PeriodicReportSenderBreakdown,
    PeriodicReportSourceBreakdown,
    PeriodicReportSummary,
    PeriodicReportTrendPoint,
} from '@/types';

const props = defineProps<{
    summary: PeriodicReportSummary;
    trend: PeriodicReportTrendPoint[];
    sourceBreakdown: PeriodicReportSourceBreakdown[];
    intakeFunnel: PeriodicReportIntakeFunnel;
    senderBreakdown: PeriodicReportSenderBreakdown[];
}>();

const maxTrend = computed(() =>
    Math.max(1, ...props.trend.map((point) => point.received)),
);
</script>

<template>
    <div class="space-y-5">
        <section aria-labelledby="summary-trend-title">
            <h2
                id="summary-trend-title"
                class="flex items-center gap-2 text-sm font-semibold"
            >
                <BarChart3 class="size-4 text-indigo-500" /> Pergerakan surat
            </h2>
            <div class="mt-3 flex h-36 items-end gap-2 rounded-xl border p-3">
                <div
                    v-for="point in trend"
                    :key="point.label"
                    class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2"
                >
                    <span class="text-[9px] font-semibold tabular-nums">
                        {{ point.received }}
                    </span>
                    <div
                        class="w-full max-w-8 rounded-t-md bg-indigo-500"
                        :style="{
                            height: `${Math.max(8, (point.received / maxTrend) * 88)}px`,
                        }"
                        :title="`${point.label}: ${point.received} surat diterima`"
                    />
                    <span class="truncate text-[9px] text-muted-foreground">
                        {{ point.label }}
                    </span>
                </div>
            </div>
        </section>

        <section aria-labelledby="summary-source-title">
            <h2
                id="summary-source-title"
                class="flex items-center gap-2 text-sm font-semibold"
            >
                <Radio class="size-4 text-indigo-500" /> Sumber surat
            </h2>
            <dl class="mt-2 space-y-2 rounded-xl border p-3">
                <div
                    v-for="source in sourceBreakdown"
                    :key="source.source"
                    class="space-y-1.5"
                >
                    <div class="flex justify-between gap-3 text-xs">
                        <dt>{{ source.label }}</dt>
                        <dd class="font-semibold tabular-nums">
                            {{ source.total }} · {{ source.percent }}%
                        </dd>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-teal-500"
                            :style="{ width: `${source.percent}%` }"
                        />
                    </div>
                </div>
            </dl>
        </section>

        <section
            class="grid grid-cols-3 gap-2"
            aria-label="Ringkasan penerimaan"
        >
            <div class="rounded-xl bg-muted/60 p-3 text-center">
                <Inbox class="mx-auto size-4 text-indigo-500" />
                <p class="mt-2 text-lg font-semibold tabular-nums">
                    {{ intakeFunnel.online_submissions }}
                </p>
                <p class="text-[10px] text-muted-foreground">Online</p>
            </div>
            <div class="rounded-xl bg-muted/60 p-3 text-center">
                <Send class="mx-auto size-4 text-teal-500" />
                <p class="mt-2 text-lg font-semibold tabular-nums">
                    {{ intakeFunnel.manual_submissions }}
                </p>
                <p class="text-[10px] text-muted-foreground">Manual</p>
            </div>
            <div class="rounded-xl bg-muted/60 p-3 text-center">
                <BarChart3 class="mx-auto size-4 text-emerald-500" />
                <p class="mt-2 text-lg font-semibold tabular-nums">
                    {{ intakeFunnel.converted_to_letters }}
                </p>
                <p class="text-[10px] text-muted-foreground">Terdaftar</p>
            </div>
        </section>

        <section aria-labelledby="summary-sender-title">
            <h2 id="summary-sender-title" class="text-sm font-semibold">
                Instansi pengirim terbanyak
            </h2>
            <ol class="mt-2 divide-y rounded-xl border px-3">
                <li
                    v-for="(sender, index) in senderBreakdown"
                    :key="sender.name"
                    class="flex items-center gap-3 py-2.5 text-xs"
                >
                    <span
                        class="grid size-6 shrink-0 place-items-center rounded-full bg-muted font-semibold"
                    >
                        {{ index + 1 }}
                    </span>
                    <span class="min-w-0 flex-1 truncate">{{
                        sender.name
                    }}</span>
                    <strong class="tabular-nums">{{ sender.total }}</strong>
                </li>
            </ol>
        </section>

        <p
            class="rounded-xl bg-muted/50 p-3 text-xs leading-5 text-muted-foreground"
        >
            Rata-rata penyelesaian:
            <strong class="text-foreground">
                {{
                    summary.average_completion_hours === null
                        ? '-'
                        : `${summary.average_completion_hours} jam`
                }}
            </strong>
        </p>
    </div>
</template>

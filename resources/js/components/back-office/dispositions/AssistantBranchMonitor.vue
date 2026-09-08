<script setup lang="ts">
import {
    CheckCircle2,
    ChevronDown,
    CircleDotDashed,
    GitFork,
    MessageSquareText,
    Play,
    ShieldCheck,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type { AssistantBranchMonitor } from '@/types';

const props = defineProps<{ monitor: AssistantBranchMonitor }>();

const safeProgress = computed(() =>
    Math.min(100, Math.max(0, props.monitor.percent_complete)),
);
</script>

<template>
    <section
        class="overflow-hidden rounded-[1.75rem] border bg-card shadow-sm"
        aria-labelledby="assistant-branch-monitor-title"
    >
        <header
            class="relative overflow-hidden border-b bg-slate-950 p-5 text-slate-100 sm:p-7 lg:p-8 dark:bg-slate-900"
        >
            <div
                class="pointer-events-none absolute -top-24 right-0 size-72 rounded-full bg-violet-500/20 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="relative grid items-end gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.72fr)]"
            >
                <div class="flex max-w-2xl items-start gap-4">
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-md"
                    >
                        <GitFork class="size-6" aria-hidden="true" />
                    </span>
                    <div>
                        <p
                            class="text-xs font-bold tracking-[0.18em] text-violet-200 uppercase"
                        >
                            Command view Asisten
                        </p>
                        <h2
                            id="assistant-branch-monitor-title"
                            class="mt-2 text-xl leading-tight font-semibold tracking-tight sm:text-2xl"
                        >
                            Progres seluruh cabang
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Pantau tiap Kepala Bagian secara independen tanpa
                            mengambil alih tindakan pada cabang mereka.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm font-medium text-slate-300">
                            Penyelesaian keseluruhan
                        </span>
                        <strong class="text-2xl tabular-nums">
                            {{ safeProgress }}%
                        </strong>
                    </div>
                    <div
                        class="mt-3 h-2.5 overflow-hidden rounded-full bg-white/10"
                        role="progressbar"
                        aria-label="Persentase cabang selesai"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-valuenow="safeProgress"
                    >
                        <span
                            class="block h-full rounded-full bg-gradient-to-r from-violet-400 to-emerald-400 transition-[width] duration-300 motion-reduce:transition-none"
                            :style="{ width: `${safeProgress}%` }"
                        />
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-400">
                        Surat tetap aktif sampai seluruh cabang terminal
                        diselesaikan.
                    </p>
                </div>
            </div>
        </header>

        <div class="grid gap-6 p-5 sm:p-7 lg:p-8">
            <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border bg-muted/20 p-4">
                    <dt
                        class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
                    >
                        <UsersRound class="size-4" aria-hidden="true" />
                        Total cabang
                    </dt>
                    <dd class="mt-2 text-2xl font-semibold tabular-nums">
                        {{ monitor.total }}
                    </dd>
                </div>
                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50/55 p-4 dark:border-amber-900 dark:bg-amber-950/20"
                >
                    <dt
                        class="flex items-center gap-2 text-xs font-medium text-amber-800 dark:text-amber-300"
                    >
                        <CircleDotDashed class="size-4" aria-hidden="true" />
                        Menunggu
                    </dt>
                    <dd class="mt-2 text-2xl font-semibold tabular-nums">
                        {{ monitor.pending }}
                    </dd>
                </div>
                <div
                    class="rounded-2xl border border-blue-200 bg-blue-50/55 p-4 dark:border-blue-900 dark:bg-blue-950/20"
                >
                    <dt
                        class="flex items-center gap-2 text-xs font-medium text-blue-800 dark:text-blue-300"
                    >
                        <Play class="size-4" aria-hidden="true" />
                        Berjalan
                    </dt>
                    <dd class="mt-2 text-2xl font-semibold tabular-nums">
                        {{ monitor.in_progress }}
                    </dd>
                </div>
                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50/55 p-4 dark:border-emerald-900 dark:bg-emerald-950/20"
                >
                    <dt
                        class="flex items-center gap-2 text-xs font-medium text-emerald-800 dark:text-emerald-300"
                    >
                        <CheckCircle2 class="size-4" aria-hidden="true" />
                        Selesai
                    </dt>
                    <dd class="mt-2 text-2xl font-semibold tabular-nums">
                        {{ monitor.completed }}
                    </dd>
                </div>
            </dl>

            <div>
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Independent branches
                        </p>
                        <h3 class="mt-1 text-lg font-semibold">
                            Status per Kepala Bagian
                        </h3>
                    </div>
                    <p class="hidden text-xs text-muted-foreground sm:block">
                        Pilih cabang untuk membuka jurnal
                    </p>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-2">
                    <details
                        v-for="branch in monitor.branches"
                        :key="branch.recipient_position.id"
                        class="group overflow-hidden rounded-3xl border bg-background shadow-xs"
                        :open="branch.status === 'IN_PROGRESS'"
                    >
                        <summary
                            class="flex min-h-24 cursor-pointer list-none items-start justify-between gap-4 p-5 transition-colors duration-200 outline-none hover:bg-muted/30 focus-visible:ring-3 focus-visible:ring-ring/50 motion-reduce:transition-none [&::-webkit-details-marker]:hidden"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant="outline"
                                        :class="
                                            dispositionRecipientStatusClass(
                                                branch.status,
                                            )
                                        "
                                    >
                                        {{
                                            dispositionRecipientStatusLabels[
                                                branch.status
                                            ]
                                        }}
                                    </Badge>
                                    <span
                                        class="text-xs text-muted-foreground tabular-nums"
                                    >
                                        Diterima
                                        {{
                                            formatRoutingDateTime(
                                                branch.received_at,
                                            )
                                        }}
                                    </span>
                                </div>
                                <p class="mt-3 font-semibold">
                                    {{ branch.recipient_position.name }}
                                </p>
                                <p
                                    class="mt-1 text-sm leading-5 text-muted-foreground"
                                >
                                    {{ branch.recipient_position.holder_name }}
                                    <template
                                        v-if="
                                            branch.recipient_position.unit_name
                                        "
                                    >
                                        ·
                                        {{
                                            branch.recipient_position.unit_name
                                        }}
                                    </template>
                                </p>
                            </div>
                            <span
                                class="flex size-11 shrink-0 items-center justify-center rounded-2xl border bg-muted/30 text-muted-foreground"
                            >
                                <ChevronDown
                                    class="size-5 transition-transform duration-200 group-open:rotate-180 motion-reduce:transition-none"
                                    aria-hidden="true"
                                />
                            </span>
                        </summary>

                        <div class="border-t bg-muted/15 p-5">
                            <dl
                                class="grid gap-3 text-sm sm:grid-cols-3"
                                aria-label="Waktu lifecycle cabang"
                            >
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Diterima
                                    </dt>
                                    <dd class="mt-1 font-medium tabular-nums">
                                        {{
                                            formatRoutingDateTime(
                                                branch.received_at,
                                            )
                                        }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Mulai
                                    </dt>
                                    <dd class="mt-1 font-medium tabular-nums">
                                        {{
                                            formatRoutingDateTime(
                                                branch.started_at,
                                            )
                                        }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Selesai
                                    </dt>
                                    <dd class="mt-1 font-medium tabular-nums">
                                        {{
                                            formatRoutingDateTime(
                                                branch.completed_at,
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>

                            <div
                                v-if="branch.follow_ups.length > 0"
                                class="mt-5 rounded-2xl border bg-background p-4"
                            >
                                <p
                                    class="flex items-center gap-2 text-xs font-semibold text-muted-foreground"
                                >
                                    <MessageSquareText
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                    Jurnal tindak lanjut
                                </p>
                                <ol class="mt-3 space-y-3">
                                    <li
                                        v-for="(
                                            followUp, index
                                        ) in branch.follow_ups"
                                        :key="`${followUp.created_at}-${index}`"
                                        class="rounded-xl bg-muted/40 p-3"
                                    >
                                        <p
                                            class="text-sm leading-6 whitespace-pre-wrap"
                                        >
                                            {{ followUp.note }}
                                        </p>
                                        <p
                                            class="mt-2 text-xs text-muted-foreground tabular-nums"
                                        >
                                            {{ followUp.created_by.name }} ·
                                            {{
                                                formatRoutingDateTime(
                                                    followUp.created_at,
                                                )
                                            }}
                                        </p>
                                    </li>
                                </ol>
                            </div>

                            <div
                                v-if="branch.completion_note"
                                class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/65 p-4 dark:border-emerald-900 dark:bg-emerald-950/20"
                            >
                                <p
                                    class="flex items-center gap-2 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
                                >
                                    <CheckCircle2
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                    Hasil penyelesaian
                                </p>
                                <p
                                    class="mt-2 text-sm leading-6 whitespace-pre-wrap"
                                >
                                    {{ branch.completion_note }}
                                </p>
                            </div>

                            <p
                                v-if="
                                    branch.follow_ups.length === 0 &&
                                    !branch.completion_note
                                "
                                class="mt-5 rounded-2xl border border-dashed p-4 text-sm text-muted-foreground"
                            >
                                Cabang ini belum memiliki jurnal atau hasil
                                penyelesaian.
                            </p>
                        </div>
                    </details>
                </div>
            </div>

            <p
                class="flex items-start gap-2 rounded-2xl border bg-muted/20 p-4 text-xs leading-5 text-muted-foreground"
            >
                <ShieldCheck
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                Monitoring ini bersifat read-only. Hanya pejabat aktif pada
                masing-masing Position Kepala Bagian yang dapat mengubah
                cabangnya sendiri.
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Check, Clock3, Inbox, Play } from '@lucide/vue';
import { computed } from 'vue';
import { formatRoutingDateTime } from '@/lib/letterRoutingPresentation';
import type { DispositionBranchLifecycle } from '@/types';

const props = defineProps<{ branch: DispositionBranchLifecycle }>();

type TimelineStep = {
    label: string;
    helper: string;
    time: string | null;
    state: 'complete' | 'current' | 'upcoming' | 'skipped';
    icon: typeof Inbox;
};

const steps = computed<TimelineStep[]>(() => {
    const completed = props.branch.status === 'COMPLETED';
    const started = props.branch.started_at !== null;

    return [
        {
            label: 'Diterima',
            helper: 'Masuk ke inbox jabatan',
            time: props.branch.received_at,
            state: 'complete',
            icon: Inbox,
        },
        {
            label: 'Ditangani',
            helper:
                completed && !started
                    ? 'Dilewati karena selesai langsung'
                    : 'Penanganan resmi dimulai',
            time: props.branch.started_at,
            state: started
                ? 'complete'
                : completed
                  ? 'skipped'
                  : props.branch.status === 'PENDING'
                    ? 'current'
                    : 'upcoming',
            icon: Play,
        },
        {
            label: 'Selesai',
            helper: 'Hasil akhir cabang dicatat',
            time: props.branch.completed_at,
            state: completed
                ? 'complete'
                : props.branch.status === 'IN_PROGRESS'
                  ? 'current'
                  : 'upcoming',
            icon: Check,
        },
    ];
});
</script>

<template>
    <section aria-labelledby="branch-progress-title">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                >
                    Lifecycle cabang
                </p>
                <h2 id="branch-progress-title" class="mt-1 font-semibold">
                    Jejak penanganan
                </h2>
            </div>
            <p class="text-xs text-muted-foreground">
                Waktu kantor Asia/Makassar
            </p>
        </div>

        <ol class="mt-4 grid gap-3 md:grid-cols-3">
            <li
                v-for="(step, index) in steps"
                :key="step.label"
                class="relative overflow-hidden rounded-2xl border p-4 transition-colors duration-200 motion-reduce:transition-none"
                :class="{
                    'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/25':
                        step.state === 'complete',
                    'border-blue-300 bg-blue-50/80 shadow-sm dark:border-blue-800 dark:bg-blue-950/25':
                        step.state === 'current',
                    'border-dashed bg-muted/25':
                        step.state === 'upcoming' || step.state === 'skipped',
                }"
            >
                <span
                    class="absolute inset-x-0 top-0 h-1"
                    :class="{
                        'bg-emerald-500': step.state === 'complete',
                        'bg-blue-600': step.state === 'current',
                        'bg-muted':
                            step.state === 'upcoming' ||
                            step.state === 'skipped',
                    }"
                    aria-hidden="true"
                />

                <div class="flex items-start gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                        :class="{
                            'bg-emerald-600 text-white':
                                step.state === 'complete',
                            'bg-blue-700 text-white': step.state === 'current',
                            'bg-muted text-muted-foreground':
                                step.state === 'upcoming' ||
                                step.state === 'skipped',
                        }"
                    >
                        <component
                            :is="step.icon"
                            class="size-4.5"
                            aria-hidden="true"
                        />
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold">{{ step.label }}</p>
                            <span
                                class="text-[11px] font-bold text-muted-foreground tabular-nums"
                            >
                                0{{ index + 1 }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">
                            {{ step.helper }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="step.time"
                    class="mt-4 flex items-center gap-2 text-xs font-medium tabular-nums"
                >
                    <Clock3 class="size-3.5" aria-hidden="true" />
                    {{ formatRoutingDateTime(step.time) }}
                </p>
                <p
                    v-else
                    class="mt-4 text-xs text-muted-foreground"
                    :class="{ italic: step.state === 'skipped' }"
                >
                    {{
                        step.state === 'skipped'
                            ? 'Tidak diperlukan'
                            : 'Belum tercatat'
                    }}
                </p>
            </li>
        </ol>
    </section>
</template>

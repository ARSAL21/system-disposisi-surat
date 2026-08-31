<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowUpRight,
    CheckCircle2,
    Clock,
    Inbox,
} from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';
import type { IntakeQueueMetrics } from '@/types';

defineProps<{
    metrics: IntakeQueueMetrics;
    activeFilter?: string;
}>();

const emit = defineEmits<{
    selectFilter: [filter: string];
}>();

const worklistCards = [
    {
        key: 'SUBMITTED',
        label: 'Perlu Screening',
        countKey: 'submitted_count' as const,
        description: 'Pengajuan baru masuk belum diperiksa',
        icon: Inbox,
        color: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-950/60',
        borderColor: 'border-blue-200 dark:border-blue-900',
        activeRing: 'ring-2 ring-blue-500 border-blue-400 dark:border-blue-600',
        actionHint: 'Klik untuk filter tabel tugas',
    },
    {
        key: 'INTERNAL_REVISION_REQUIRED',
        label: 'Perbaikan dari Kabag',
        countKey: 'internal_revision_count' as const,
        description: 'Dikembalikan untuk koreksi staf',
        icon: AlertCircle,
        color: 'text-rose-600 dark:text-rose-400',
        bgColor: 'bg-rose-50 dark:bg-rose-950/60',
        borderColor: 'border-rose-200 dark:border-rose-900',
        activeRing: 'ring-2 ring-rose-500 border-rose-400 dark:border-rose-600',
        actionHint: 'Klik untuk filter tabel tugas',
    },
];

const externalQueueCards = [
    {
        key: 'READY_FOR_APPROVAL',
        label: 'Di Meja Kabag',
        countKey: 'ready_for_approval_count' as const,
        description: 'Lolos screening, menunggu persetujuan',
        icon: Clock,
        color: 'text-indigo-600 dark:text-indigo-400',
        bgColor: 'bg-indigo-50 dark:bg-indigo-950/60',
        borderColor: 'border-indigo-200 dark:border-indigo-900',
        href: '/back-office/intake/submissions?status=READY_FOR_APPROVAL',
        actionHint: 'Buka antrean persetujuan',
    },
    {
        key: 'REGISTERED',
        label: 'Selesai Diregistrasi',
        countKey: 'registered_count' as const,
        description: 'Surat resmi telah diagendakan',
        icon: CheckCircle2,
        color: 'text-emerald-600 dark:text-emerald-400',
        bgColor: 'bg-emerald-50 dark:bg-emerald-950/60',
        borderColor: 'border-emerald-200 dark:border-emerald-900',
        href: '/back-office/intake/submissions?status=REGISTERED',
        actionHint: 'Buka arsip registrasi',
    },
];
</script>

<template>
    <div
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        aria-label="Ringkasan metrik antrean surat"
    >
        <!-- Worklist Filter Cards (SUBMITTED & INTERNAL_REVISION_REQUIRED) -->
        <Card
            v-for="card in worklistCards"
            :key="card.key"
            class="group relative cursor-pointer border transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            :class="[
                card.borderColor,
                activeFilter === card.key ? card.activeRing : 'bg-card',
            ]"
            @click="
                emit(
                    'selectFilter',
                    activeFilter === card.key ? 'ALL' : card.key,
                )
            "
        >
            <CardContent class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            {{ card.label }}
                        </span>
                        <div
                            class="text-2xl font-extrabold tracking-tight text-foreground sm:text-3xl"
                        >
                            {{ metrics[card.countKey] ?? 0 }}
                        </div>
                    </div>

                    <div
                        class="flex size-11 items-center justify-center rounded-2xl transition-transform duration-200 group-hover:scale-110"
                        :class="[card.bgColor, card.color]"
                    >
                        <component
                            :is="card.icon"
                            class="size-5"
                            aria-hidden="true"
                        />
                    </div>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-muted-foreground">
                    {{ card.description }}
                </p>

                <div
                    class="mt-3 flex items-center justify-between border-t border-border/50 pt-2.5 text-[11px] font-medium text-muted-foreground"
                >
                    <span>{{ card.actionHint }}</span>
                    <span
                        v-if="activeFilter === card.key"
                        class="font-bold text-primary"
                    >
                        Aktif
                    </span>
                </div>
            </CardContent>
        </Card>

        <!-- External Queue Links (READY_FOR_APPROVAL & REGISTERED) -->
        <Link
            v-for="card in externalQueueCards"
            :key="card.key"
            :href="card.href"
            class="block"
        >
            <Card
                class="group relative h-full border bg-card transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                :class="[card.borderColor]"
            >
                <CardContent class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <span
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                {{ card.label }}
                            </span>
                            <div
                                class="text-2xl font-extrabold tracking-tight text-foreground sm:text-3xl"
                            >
                                {{ metrics[card.countKey] ?? 0 }}
                            </div>
                        </div>

                        <div
                            class="flex size-11 items-center justify-center rounded-2xl transition-transform duration-200 group-hover:scale-110"
                            :class="[card.bgColor, card.color]"
                        >
                            <component
                                :is="card.icon"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </div>
                    </div>

                    <p
                        class="mt-3 text-xs leading-relaxed text-muted-foreground"
                    >
                        {{ card.description }}
                    </p>

                    <div
                        class="mt-3 flex items-center justify-between border-t border-border/50 pt-2.5 text-[11px] font-medium text-primary"
                    >
                        <span>{{ card.actionHint }}</span>
                        <ArrowUpRight
                            class="size-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                            aria-hidden="true"
                        />
                    </div>
                </CardContent>
            </Card>
        </Link>
    </div>
</template>

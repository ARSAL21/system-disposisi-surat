<script setup lang="ts">
import { Check, FileScan, FileText, UserRound } from '@lucide/vue';
import type { Component } from 'vue';
import type { ManualIntakeStep } from '@/types';

const props = defineProps<{ currentStep: ManualIntakeStep }>();
const emit = defineEmits<{ select: [step: ManualIntakeStep] }>();

const steps: Array<{
    id: ManualIntakeStep;
    title: string;
    short: string;
    icon: Component;
}> = [
    {
        id: 1,
        title: 'Asal surat',
        short: 'Pengirim & penerimaan',
        icon: UserRound,
    },
    {
        id: 2,
        title: 'Identitas surat',
        short: 'Nomor, tanggal & perihal',
        icon: FileText,
    },
    {
        id: 3,
        title: 'Scan & periksa',
        short: 'PDF dan konfirmasi',
        icon: FileScan,
    },
];

function canOpen(step: ManualIntakeStep): boolean {
    return step <= props.currentStep;
}
</script>

<template>
    <nav
        class="rounded-2xl border bg-card p-2 shadow-xs"
        aria-label="Tahapan pencatatan surat manual"
    >
        <ol class="grid gap-2 md:grid-cols-3">
            <li v-for="step in steps" :key="step.id" class="min-w-0">
                <button
                    type="button"
                    :disabled="!canOpen(step.id)"
                    :aria-current="currentStep === step.id ? 'step' : undefined"
                    :class="[
                        'flex min-h-16 w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors focus-visible:ring-3 focus-visible:ring-ring/30 focus-visible:outline-none motion-reduce:transition-none',
                        currentStep === step.id
                            ? 'bg-emerald-700 text-white shadow-sm dark:bg-emerald-500 dark:text-emerald-950'
                            : step.id < currentStep
                              ? 'cursor-pointer bg-emerald-50 text-emerald-950 hover:bg-emerald-100 dark:bg-emerald-950/35 dark:text-emerald-100 dark:hover:bg-emerald-950/55'
                              : 'cursor-not-allowed text-muted-foreground opacity-65',
                    ]"
                    @click="canOpen(step.id) && emit('select', step.id)"
                >
                    <span
                        :class="[
                            'flex size-10 shrink-0 items-center justify-center rounded-xl border',
                            currentStep === step.id
                                ? 'border-white/25 bg-white/15'
                                : step.id < currentStep
                                  ? 'border-emerald-200 bg-white text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                  : 'border-border bg-muted/50',
                        ]"
                    >
                        <Check
                            v-if="step.id < currentStep"
                            class="size-5"
                            aria-hidden="true"
                        />
                        <component
                            :is="step.icon"
                            v-else
                            class="size-5"
                            aria-hidden="true"
                        />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-medium opacity-75">
                            Tahap {{ step.id }}
                        </span>
                        <span
                            class="mt-0.5 block truncate text-sm font-semibold"
                        >
                            {{ step.title }}
                        </span>
                        <span class="mt-0.5 hidden text-xs opacity-75 sm:block">
                            {{ step.short }}
                        </span>
                    </span>
                </button>
            </li>
        </ol>
    </nav>
</template>

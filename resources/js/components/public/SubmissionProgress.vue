<script setup lang="ts">
import { Check, FileText, Send } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { LetterSubmission } from '@/types';

const props = defineProps<{
    submission?: LetterSubmission;
    createStep?: boolean;
}>();

const currentStep = computed(() => {
    if (props.createStep || !props.submission) {
        return 1;
    }

    if (props.submission.status !== 'DRAFT') {
        return 3;
    }

    return props.submission.document ? 3 : 2;
});

const steps = [
    {
        title: 'Data surat',
        description: 'Simpan informasi surat',
        icon: FileText,
    },
    { title: 'Dokumen', description: 'Unggah PDF privat', icon: FileText },
    { title: 'Kirim', description: 'Periksa dan konfirmasi', icon: Send },
];
</script>

<template>
    <ol
        class="grid gap-2 rounded-[1.75rem] border bg-card/85 p-3 shadow-[0_18px_60px_-44px_rgba(20,47,43,0.45)] backdrop-blur md:grid-cols-3"
        aria-label="Tahapan pengajuan surat"
    >
        <li
            v-for="(step, index) in steps"
            :key="step.title"
            :aria-current="currentStep === index + 1 ? 'step' : undefined"
            :class="
                cn(
                    'flex min-h-20 items-center gap-3 rounded-2xl px-4 py-3 transition-colors duration-200',
                    currentStep === index + 1 &&
                        'bg-primary text-primary-foreground',
                    currentStep > index + 1 &&
                        'bg-success text-success-foreground',
                    currentStep < index + 1 && 'text-muted-foreground',
                )
            "
        >
            <span
                :class="
                    cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-xl border',
                        currentStep === index + 1
                            ? 'border-white/20 bg-white/10'
                            : 'border-border bg-background/70',
                    )
                "
            >
                <Check v-if="currentStep > index + 1" class="size-4" />
                <component v-else :is="step.icon" class="size-4" />
            </span>
            <span>
                <span class="block text-sm font-semibold">{{
                    step.title
                }}</span>
                <span
                    :class="
                        cn(
                            'mt-0.5 block text-xs',
                            currentStep === index + 1
                                ? 'text-primary-foreground/75'
                                : 'text-muted-foreground',
                        )
                    "
                >
                    {{ step.description }}
                </span>
            </span>
        </li>
    </ol>
</template>

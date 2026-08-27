<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    label: string;
    value: number;
    description: string;
    icon: LucideIcon;
    tone: 'primary' | 'blue' | 'purple';
}>();

const tones = {
    primary: 'bg-secondary text-secondary-foreground',
    blue: 'bg-info text-info-foreground',
    purple: 'bg-accent text-accent-foreground',
};
</script>

<template>
    <article
        class="flex min-h-36 items-start gap-4 rounded-2xl border bg-card p-5 shadow-sm"
    >
        <span
            :class="[
                'flex size-12 shrink-0 items-center justify-center rounded-2xl',
                tones[tone],
            ]"
            aria-hidden="true"
        >
            <component :is="icon" class="size-5" />
        </span>
        <div class="min-w-0">
            <p class="text-3xl font-semibold tracking-tight tabular-nums">
                {{ value }}
            </p>
            <h2 class="mt-1 text-sm font-semibold">{{ label }}</h2>
            <p class="mt-1 text-xs leading-5 text-muted-foreground">
                {{ description }}
            </p>
        </div>
    </article>
</template>

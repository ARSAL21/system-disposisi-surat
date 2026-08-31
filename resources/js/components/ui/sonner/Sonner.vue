<script lang="ts" setup>
import type { ToasterProps } from 'vue-sonner';
import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
    XIcon,
} from '@lucide/vue';
import { Toaster as Sonner } from 'vue-sonner';
import { useAppearance } from '@/composables/useAppearance';
import { cn } from '@/lib/utils';

import 'vue-sonner/style.css';

const props = withDefaults(defineProps<ToasterProps>(), {
    position: 'bottom-right',
});

const { resolvedAppearance } = useAppearance();
</script>

<template>
    <Sonner
        :theme="resolvedAppearance"
        :class="cn('toaster group', props.class)"
        :style="{
            '--normal-bg': 'var(--toast-bg)',
            '--normal-text': 'var(--toast-foreground)',
            '--normal-border': 'var(--toast-border)',
            '--border-radius': 'var(--radius)',
        }"
        :toast-options="{
            classes: {
                toast: 'group toast font-sans',
                title: 'font-semibold text-sm',
                description: 'text-xs',
                actionButton: 'font-medium',
                cancelButton: 'font-medium',
                closeButton: 'transition-colors',
            },
        }"
        v-bind="props"
    >
        <template #success-icon>
            <CircleCheckIcon class="size-4 shrink-0 text-[var(--toast-icon-success)]" />
        </template>
        <template #info-icon>
            <InfoIcon class="size-4 shrink-0 text-[var(--toast-icon-info)]" />
        </template>
        <template #warning-icon>
            <TriangleAlertIcon class="size-4 shrink-0 text-[var(--toast-icon-warning)]" />
        </template>
        <template #error-icon>
            <OctagonXIcon class="size-4 shrink-0 text-[var(--toast-icon-error)]" />
        </template>
        <template #loading-icon>
            <div>
                <Loader2Icon class="size-4 shrink-0 animate-spin text-current" />
            </div>
        </template>
        <template #close-icon>
            <XIcon class="size-4" />
        </template>
    </Sonner>
</template>

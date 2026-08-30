<script setup lang="ts">
import { ArrowRight, Minus } from '@lucide/vue';
import { formatLetterActivityValue } from '@/lib/letterActivityPresentation';
import type { LetterActivityValue } from '@/types';

defineProps<{
    title: string;
    changes: Record<string, LetterActivityValue> | null;
    tone: 'before' | 'after';
}>();

function labelFor(key: string): string {
    return key
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}
</script>

<template>
    <section
        :class="[
            'rounded-2xl border p-4',
            tone === 'before'
                ? 'border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/50'
                : 'border-indigo-200 bg-indigo-50/70 dark:border-indigo-900 dark:bg-indigo-950/30',
        ]"
    >
        <h3 class="flex items-center gap-2 text-sm font-semibold">
            <Minus
                v-if="tone === 'before'"
                class="size-4 text-slate-500"
                aria-hidden="true"
            />
            <ArrowRight
                v-else
                class="size-4 text-indigo-600 dark:text-indigo-300"
                aria-hidden="true"
            />
            {{ title }}
        </h3>
        <dl v-if="changes" class="mt-3 space-y-3">
            <div v-for="(value, key) in changes" :key="key">
                <dt class="text-xs font-medium text-muted-foreground">
                    {{ labelFor(String(key)) }}
                </dt>
                <dd class="mt-0.5 text-sm leading-6 text-slate-900 dark:text-white">
                    {{ formatLetterActivityValue(value) }}
                </dd>
            </div>
        </dl>
        <p v-else class="mt-3 text-sm text-muted-foreground">
            Tidak ada nilai sebelumnya.
        </p>
    </section>
</template>

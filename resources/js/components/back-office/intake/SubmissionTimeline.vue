<script setup lang="ts">
import { Check, Circle } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatSubmissionDateTime } from '@/lib/submissionPresentation';
import type { IntakeTimelineItem } from '@/types';

defineProps<{ items: IntakeTimelineItem[] }>();
</script>

<template>
    <Card class="shadow-sm">
        <CardHeader>
            <CardTitle>Riwayat pengajuan surat</CardTitle>
        </CardHeader>
        <CardContent>
            <ol class="space-y-0">
                <li
                    v-for="(item, index) in items"
                    :key="item.id"
                    class="relative flex gap-3 pb-6 last:pb-0"
                >
                    <span
                        v-if="index < items.length - 1"
                        class="absolute top-7 bottom-0 left-3 w-px bg-border"
                        aria-hidden="true"
                    />
                    <span
                        :class="[
                            'relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full border',
                            item.state === 'complete'
                                ? 'border-emerald-600 bg-emerald-600 text-white'
                                : item.state === 'current'
                                  ? 'border-blue-600 bg-blue-600 text-white'
                                  : 'bg-background text-muted-foreground',
                        ]"
                    >
                        <Check
                            v-if="item.state === 'complete'"
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        <Circle
                            v-else
                            class="size-2.5 fill-current"
                            aria-hidden="true"
                        />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold">{{ item.title }}</p>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">
                            {{ item.description }}
                        </p>
                        <time
                            v-if="item.occurred_at"
                            class="mt-1.5 block text-xs text-muted-foreground tabular-nums"
                        >
                            {{ formatSubmissionDateTime(item.occurred_at) }}
                        </time>
                    </div>
                </li>
            </ol>
        </CardContent>
    </Card>
</template>

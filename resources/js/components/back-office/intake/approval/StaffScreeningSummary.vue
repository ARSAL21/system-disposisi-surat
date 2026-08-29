<script setup lang="ts">
import { Check, ClipboardCheck, MessageSquareText } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { StaffScreeningReview } from '@/types';

defineProps<{ review: StaffScreeningReview }>();
</script>

<template>
    <Card class="shadow-sm">
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>Hasil pemeriksaan petugas</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Ringkasan kelengkapan sebelum surat diajukan kepada
                        Anda.
                    </p>
                </div>
                <span
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
                >
                    <ClipboardCheck class="size-4" aria-hidden="true" />
                    Seluruh pemeriksaan lengkap
                </span>
            </div>
        </CardHeader>
        <CardContent class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <ul
                class="grid gap-2 sm:grid-cols-2"
                aria-label="Hasil pemeriksaan"
            >
                <li
                    v-for="item in review.checklist"
                    :key="item.id"
                    class="flex gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/55 p-4 dark:border-emerald-900 dark:bg-emerald-950/15"
                >
                    <span
                        class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white"
                    >
                        <Check class="size-3" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold">{{ item.label }}</p>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">
                            {{ item.description }}
                        </p>
                    </div>
                </li>
            </ul>

            <div class="rounded-2xl border bg-muted/35 p-4">
                <p class="flex items-center gap-2 text-sm font-semibold">
                    <MessageSquareText
                        class="size-4 text-violet-600"
                        aria-hidden="true"
                    />
                    Catatan pengantar petugas
                </p>
                <p class="mt-3 text-sm leading-6 text-muted-foreground">
                    {{ review.note || 'Tidak ada catatan tambahan.' }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>

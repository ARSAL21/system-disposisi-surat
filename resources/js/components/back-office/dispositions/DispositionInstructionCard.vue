<script setup lang="ts">
import {
    ClipboardList,
    MessageSquareText,
    Send,
    ShieldCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type { DispositionInboxItem } from '@/types';

defineProps<{ disposition: DispositionInboxItem }>();
</script>

<template>
    <Card class="border-blue-200/80 py-0 shadow-sm dark:border-blue-950">
        <CardHeader class="border-b p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                    >
                        <ClipboardList class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <CardTitle>Instruksi disposisi</CardTitle>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            Arahan resmi yang melekat pada cabang jabatan Anda.
                        </p>
                    </div>
                </div>
                <Badge
                    variant="outline"
                    :class="dispositionRecipientStatusClass(disposition.status)"
                >
                    {{ dispositionRecipientStatusLabels[disposition.status] }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="grid gap-4 p-5 sm:p-6">
            <div
                class="flex items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/65 p-4 dark:border-violet-900 dark:bg-violet-950/25"
            >
                <Send
                    class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">Dikirim oleh</p>
                    <p class="mt-1 font-semibold">
                        {{ disposition.sender.name }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ disposition.sender.position }}
                        <template v-if="disposition.sender.unit">
                            · {{ disposition.sender.unit }}
                        </template>
                    </p>
                    <p class="mt-2 text-xs font-medium tabular-nums">
                        {{ formatRoutingDateTime(disposition.received_at) }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border p-4">
                <p class="text-xs font-medium text-muted-foreground">
                    Label instruksi
                </p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <li
                        v-for="instruction in disposition.instructions"
                        :key="instruction.code"
                        class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300"
                    >
                        {{ instruction.name }}
                    </li>
                </ul>
            </div>

            <div
                v-if="disposition.instruction_note"
                class="flex items-start gap-3 rounded-2xl bg-muted/55 p-4"
            >
                <MessageSquareText
                    class="mt-0.5 size-5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">
                        Catatan tambahan
                    </p>
                    <p class="mt-2 text-sm leading-6 whitespace-pre-wrap">
                        {{ disposition.instruction_note }}
                    </p>
                </div>
            </div>

            <p
                class="flex items-start gap-2 text-xs leading-5 text-muted-foreground"
            >
                <ShieldCheck
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                Akses diberikan melalui Position Assignment aktif dan tetap
                diverifikasi server pada setiap permintaan.
            </p>
        </CardContent>
    </Card>
</template>

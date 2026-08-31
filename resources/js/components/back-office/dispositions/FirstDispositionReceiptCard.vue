<script setup lang="ts">
import {
    CheckCircle2,
    ClipboardCheck,
    MessageSquareText,
    ShieldCheck,
    UserRoundCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type { FirstDispositionReceipt } from '@/types';

defineProps<{ disposition: FirstDispositionReceipt }>();
</script>

<template>
    <Card class="border-emerald-200/80 py-0 shadow-sm dark:border-emerald-950">
        <CardHeader class="border-b p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                    >
                        <ClipboardCheck class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <CardTitle>Disposisi pertama terkirim</CardTitle>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            Bukti keputusan eksekutif dan tujuan jabatan resmi.
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
                class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/65 p-4 dark:border-emerald-900 dark:bg-emerald-950/25"
            >
                <UserRoundCheck
                    class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">
                        Asisten penerima
                    </p>
                    <p class="mt-1 font-semibold">
                        {{ disposition.recipient_position.name }}
                    </p>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ disposition.recipient_position.holder_name }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border p-4">
                <div class="flex items-center gap-2">
                    <CheckCircle2
                        class="size-4 text-blue-700 dark:text-blue-300"
                        aria-hidden="true"
                    />
                    <p class="text-xs font-medium text-muted-foreground">
                        Instruksi resmi
                    </p>
                </div>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <li
                        v-for="instruction in disposition.instructions"
                        :key="instruction.code"
                        class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300"
                    >
                        {{ instruction.name }}
                    </li>
                </ul>

                <div
                    v-if="disposition.instruction_note"
                    class="mt-4 flex items-start gap-3 rounded-xl bg-muted/55 p-3"
                >
                    <MessageSquareText
                        class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <p
                        class="whitespace-pre-wrap text-sm leading-6 text-muted-foreground"
                    >
                        {{ disposition.instruction_note }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl bg-muted/45 p-4 text-sm">
                <p class="font-semibold">{{ disposition.disposed_by.name }}</p>
                <p class="mt-1 text-muted-foreground">
                    {{ disposition.disposed_by.position }}
                    <template v-if="disposition.disposed_by.unit">
                        · {{ disposition.disposed_by.unit }}
                    </template>
                </p>
                <p class="mt-2 text-xs font-medium tabular-nums">
                    {{ formatRoutingDateTime(disposition.disposed_at) }}
                </p>
            </div>

            <p
                class="flex items-start gap-2 text-xs leading-5 text-muted-foreground"
            >
                <ShieldCheck
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                Identitas pengirim dan Position Assignment historis ditetapkan
                server ketika disposisi dibuat.
            </p>
        </CardContent>
    </Card>
</template>

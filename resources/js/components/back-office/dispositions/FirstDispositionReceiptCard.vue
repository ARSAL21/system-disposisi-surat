<script setup lang="ts">
import {
    CheckCircle2,
    ClipboardCheck,
    MessageSquareText,
    ShieldCheck,
    UserRoundCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type { FirstDispositionReceipt } from '@/types';

defineProps<{ disposition: FirstDispositionReceipt }>();
</script>

<template>
    <section class="rounded-3xl border border-emerald-500/30 bg-card p-6 shadow-sm dark:border-emerald-500/20 dark:bg-slate-900/80">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 pb-4 dark:border-border/40">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                    <ClipboardCheck class="size-5" />
                </div>
                <div>
                    <h2 class="font-['Syne',sans-serif] text-base font-bold text-foreground">
                        Disposisi Pertama Diterbitkan
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Bukti penetapan instruksi dan penugasan Asisten.
                    </p>
                </div>
            </div>

            <Badge
                variant="outline"
                class="border-emerald-500/30 bg-emerald-500/10 px-3 py-1 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-300"
            >
                {{ disposition.recipients.length }} Asisten Ditugaskan
            </Badge>
        </div>

        <div class="mt-5 space-y-4">
            <!-- Recipients List -->
            <div class="space-y-2">
                <span class="font-mono text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                    Asisten Penerima Disposisi:
                </span>
                <div class="grid gap-2">
                    <div
                        v-for="recipient in disposition.recipients"
                        :key="recipient.recipient_position.id"
                        class="flex items-start justify-between gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-3.5 dark:bg-emerald-950/20"
                    >
                        <div class="flex items-start gap-2.5 min-w-0">
                            <UserRoundCheck class="mt-0.5 size-4.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <div class="min-w-0 text-xs">
                                <p class="font-bold text-foreground truncate">
                                    {{ recipient.recipient_position.name }}
                                </p>
                                <p class="text-muted-foreground truncate">
                                    {{ recipient.recipient_position.holder_name }}
                                </p>
                            </div>
                        </div>

                        <Badge
                            variant="outline"
                            class="px-2 py-0.5 font-mono text-[10px] font-bold shrink-0"
                            :class="dispositionRecipientStatusClass(recipient.status)"
                        >
                            {{ dispositionRecipientStatusLabels[recipient.status] }}
                        </Badge>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="rounded-2xl border border-border/70 bg-background/80 p-4 dark:bg-slate-950/60">
                <div class="flex items-center gap-2">
                    <CheckCircle2 class="size-4 text-indigo-600 dark:text-indigo-400" />
                    <span class="font-mono text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                        Instruksi Resmi Pimpinan:
                    </span>
                </div>

                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    <span
                        v-for="instruction in disposition.instructions"
                        :key="instruction.code"
                        class="rounded-xl border border-indigo-500/25 bg-indigo-500/10 px-2.5 py-1 font-mono text-[11px] font-bold text-indigo-700 dark:text-indigo-300"
                    >
                        {{ instruction.name }}
                    </span>
                </div>

                <div
                    v-if="disposition.instruction_note"
                    class="mt-3 flex items-start gap-2.5 rounded-xl bg-muted/50 p-3 text-xs"
                >
                    <MessageSquareText class="mt-0.5 size-4 text-muted-foreground shrink-0" />
                    <p class="whitespace-pre-wrap text-muted-foreground leading-relaxed">
                        {{ disposition.instruction_note }}
                    </p>
                </div>
            </div>

            <!-- Disposed By Author Metadata -->
            <div class="flex items-center justify-between gap-3 rounded-2xl bg-muted/40 p-3.5 text-xs">
                <div class="min-w-0">
                    <p class="font-bold text-foreground">{{ disposition.disposed_by.name }}</p>
                    <p class="text-[11px] text-muted-foreground">
                        {{ disposition.disposed_by.position }}
                        <template v-if="disposition.disposed_by.unit">· {{ disposition.disposed_by.unit }}</template>
                    </p>
                </div>
                <span class="font-mono text-[10px] text-muted-foreground tabular-nums shrink-0">
                    {{ formatRoutingDateTime(disposition.disposed_at) }}
                </span>
            </div>

            <div class="flex items-center gap-2 text-[11px] text-muted-foreground">
                <ShieldCheck class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                <span>Disposisi tersimpan secara append-only dalam audit log.</span>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import {
    Building2,
    CalendarClock,
    FileDigit,
    FileText,
    Mail,
} from '@lucide/vue';
import { formatRoutingDateTime } from '@/lib/letterRoutingPresentation';
import type { LetterRoutingItem } from '@/types';

defineProps<{ letter: LetterRoutingItem }>();
</script>

<template>
    <section
        class="rounded-3xl border border-border/80 bg-card p-6 shadow-sm dark:border-border/60 dark:bg-slate-900/80"
    >
        <!-- Section Header -->
        <div
            class="flex items-center gap-3 border-b border-border/60 pb-4 dark:border-border/40"
        >
            <div
                class="flex size-10 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400"
            >
                <FileText class="size-5" />
            </div>
            <div>
                <h2
                    class="font-['Syne',sans-serif] text-base font-bold text-foreground sm:text-lg"
                >
                    Dossier Identitas Naskah Dinas
                </h2>
                <p class="text-xs text-muted-foreground">
                    Verifikasi kelengkapan dan keabsahan atribut surat resmi
                    sebelum penetapan instruksi.
                </p>
            </div>
        </div>

        <!-- 4-Grid Attributes -->
        <div class="mt-5 grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <!-- Agenda -->
            <div
                class="flex items-start gap-3 rounded-2xl border border-border/60 bg-muted/40 p-4"
            >
                <FileDigit
                    class="mt-0.5 size-4.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                />
                <div class="min-w-0">
                    <span
                        class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Nomor Agenda Surat
                    </span>
                    <p
                        class="mt-0.5 font-mono text-sm font-bold text-foreground"
                    >
                        #{{ letter.agenda_number }}
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        Tahun Anggaran {{ letter.agenda_year }}
                    </p>
                </div>
            </div>

            <!-- External Letter Number -->
            <div
                class="flex items-start gap-3 rounded-2xl border border-border/60 bg-muted/40 p-4"
            >
                <Mail
                    class="mt-0.5 size-4.5 shrink-0 text-violet-600 dark:text-violet-400"
                />
                <div class="min-w-0">
                    <span
                        class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Nomor Surat Pengirim
                    </span>
                    <p
                        class="mt-0.5 font-mono text-xs font-bold break-all text-foreground"
                    >
                        {{ letter.external_letter_number || '-' }}
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        Nomor resmi dari instansi pengirim
                    </p>
                </div>
            </div>

            <!-- Sender Org -->
            <div
                class="flex items-start gap-3 rounded-2xl border border-border/60 bg-muted/40 p-4"
            >
                <Building2
                    class="mt-0.5 size-4.5 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <div class="min-w-0">
                    <span
                        class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Instansi / Lembaga Pengirim
                    </span>
                    <p class="mt-0.5 text-xs font-bold text-foreground">
                        {{ letter.sender_organization_name }}
                    </p>
                </div>
            </div>

            <!-- Received At -->
            <div
                class="flex items-start gap-3 rounded-2xl border border-border/60 bg-muted/40 p-4"
            >
                <CalendarClock
                    class="mt-0.5 size-4.5 shrink-0 text-emerald-600 dark:text-emerald-400"
                />
                <div class="min-w-0">
                    <span
                        class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Waktu Penerimaan Resmi
                    </span>
                    <p
                        class="mt-0.5 font-mono text-xs font-bold text-foreground tabular-nums"
                    >
                        {{ formatRoutingDateTime(letter.received_at) }}
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        Tercatat di Bagian Umum
                    </p>
                </div>
            </div>
        </div>

        <!-- Subject Detail Block -->
        <div
            class="mt-4 rounded-2xl border border-border/70 bg-background/80 p-4.5 dark:bg-slate-950/60"
        >
            <span
                class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
            >
                Perihal & Pokok Naskah Dinas:
            </span>
            <p
                class="mt-2 text-sm leading-relaxed font-semibold text-foreground"
            >
                {{ letter.subject }}
            </p>
        </div>
    </section>
</template>

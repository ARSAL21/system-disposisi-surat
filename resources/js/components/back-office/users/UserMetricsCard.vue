<script setup lang="ts">
import { CheckCircle, Clock, FileText, Inbox, ShieldCheck } from '@lucide/vue';
import type { UserSubmissionMetrics } from '@/types/user-management';

defineProps<{
    metrics: UserSubmissionMetrics;
    accountType: 'INTERNAL' | 'PUBLIC';
}>();
</script>

<template>
    <div class="rounded-2xl border bg-card p-5 shadow-xs">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="rounded-xl bg-primary/10 p-2 text-primary">
                    <FileText class="size-5" />
                </span>
                <div>
                    <h3 class="text-sm font-bold text-foreground">
                        Metrik Persuratan Aman
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        {{
                            accountType === 'PUBLIC'
                                ? 'Statistik pengajuan surat masuk'
                                : 'Statistik operasional teragregasi'
                        }}
                    </p>
                </div>
            </div>
            <span class="flex items-center gap-1 text-xs text-muted-foreground">
                <ShieldCheck class="size-3.5 text-emerald-500" />
                Zero-Leakage
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <!-- Total -->
            <div class="rounded-xl border bg-muted/30 p-3">
                <p class="text-xs font-medium text-muted-foreground">
                    Total Diajukan
                </p>
                <p class="mt-1 text-xl font-bold text-foreground tabular-nums">
                    {{ metrics.total }}
                </p>
            </div>

            <!-- Menunggu / Submitted -->
            <div
                class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3"
            >
                <p
                    class="flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400"
                >
                    <Clock class="size-3" />
                    Diproses
                </p>
                <p class="mt-1 text-xl font-bold text-foreground tabular-nums">
                    {{ metrics.submitted }}
                </p>
            </div>

            <!-- Terverifikasi / Disetujui -->
            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3"
            >
                <p
                    class="flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                >
                    <CheckCircle class="size-3" />
                    Selesai
                </p>
                <p class="mt-1 text-xl font-bold text-foreground tabular-nums">
                    {{ metrics.verified }}
                </p>
            </div>

            <!-- Ditolak / Draf -->
            <div class="rounded-xl border bg-muted/30 p-3">
                <p
                    class="flex items-center gap-1 text-xs font-medium text-muted-foreground"
                >
                    <Inbox class="size-3" />
                    Draf / Batal
                </p>
                <p class="mt-1 text-xl font-bold text-foreground tabular-nums">
                    {{ metrics.draft + metrics.rejected }}
                </p>
            </div>
        </div>

        <p class="mt-3 text-[11px] text-muted-foreground italic">
            * Sesuai standar keamanan sistem, ringkasan ini hanya menampilkan
            agregat kuantitas tanpa membocorkan judul, perihal, atau konten
            dokumen privat.
        </p>
    </div>
</template>

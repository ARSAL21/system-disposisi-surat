<script setup lang="ts">
import {
    CheckCircle2,
    CircleDotDashed,
    GitBranch,
    Hourglass,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { ExecutiveBranchProgress } from '@/types';

const props = defineProps<{ progress: ExecutiveBranchProgress }>();

const phasePresentation = computed(() => {
    if (props.progress.phase === 'AWAITING_DECISION') {
        return {
            label: 'Menunggu Disposisi',
            helper: 'Surat belum didisposisikan kepada Asisten.',
            badge: 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        };
    }

    if (props.progress.phase === 'AWAITING_FORWARDING') {
        return {
            label: 'Menunggu Penerusan',
            helper: 'Asisten belum membentuk cabang penugasan ke Kepala Bagian.',
            badge: 'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
        };
    }

    if (props.progress.phase === 'IN_PROGRESS') {
        return {
            label: 'Tindak Lanjut Berjalan',
            helper: 'Sedikitnya satu cabang disposisi masih aktif ditangani.',
            badge: 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
        };
    }

    return {
        label: 'Seluruh Cabang Selesai',
        helper: 'Semua cabang tindak lanjut teknis telah dituntaskan.',
        badge: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    };
});

const safeProgress = computed(() =>
    Math.min(100, Math.max(0, props.progress.percent_complete)),
);
</script>

<template>
    <section
        class="rounded-3xl border border-indigo-500/30 bg-card p-6 shadow-sm dark:border-indigo-500/20 dark:bg-slate-900/80"
        aria-labelledby="executive-branch-progress-title"
    >
        <!-- Header -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 pb-4 dark:border-border/40"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400"
                >
                    <GitBranch class="size-5" />
                </div>
                <div>
                    <h2
                        id="executive-branch-progress-title"
                        class="font-['Syne',sans-serif] text-base font-bold text-foreground"
                    >
                        Pemantauan Cabang Disposisi
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        {{ phasePresentation.helper }}
                    </p>
                </div>
            </div>

            <Badge
                variant="outline"
                class="px-2.5 py-0.5 font-mono text-[11px] font-bold"
                :class="phasePresentation.badge"
            >
                {{ phasePresentation.label }}
            </Badge>
        </div>

        <!-- Progress Bar Section -->
        <div class="mt-5 space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span
                    class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                >
                    Penyelesaian Cabang Disposisi:
                </span>
                <span
                    class="font-mono text-sm font-extrabold text-foreground tabular-nums"
                >
                    {{ safeProgress }}%
                </span>
            </div>

            <div
                class="h-2 w-full overflow-hidden rounded-full bg-muted dark:bg-slate-800"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500 transition-all duration-500"
                    :style="{ width: `${safeProgress}%` }"
                />
            </div>
        </div>

        <!-- 4 Metrics Grid -->
        <div class="mt-5 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            <div
                class="rounded-2xl border border-border/70 bg-background/80 p-3 text-center dark:bg-slate-950/60"
            >
                <span
                    class="font-mono text-[10px] text-muted-foreground uppercase"
                    >Total</span
                >
                <p
                    class="font-['Syne',sans-serif] text-xl font-bold text-foreground tabular-nums"
                >
                    {{ progress.total }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-border/70 bg-background/80 p-3 text-center dark:bg-slate-950/60"
            >
                <div
                    class="flex items-center justify-center gap-1 font-mono text-[10px] text-amber-600 uppercase dark:text-amber-400"
                >
                    <CircleDotDashed class="size-3" />
                    <span>Menunggu</span>
                </div>
                <p
                    class="font-['Syne',sans-serif] text-xl font-bold text-foreground tabular-nums"
                >
                    {{ progress.pending }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-border/70 bg-background/80 p-3 text-center dark:bg-slate-950/60"
            >
                <div
                    class="flex items-center justify-center gap-1 font-mono text-[10px] text-sky-600 uppercase dark:text-sky-400"
                >
                    <Hourglass class="size-3" />
                    <span>Berjalan</span>
                </div>
                <p
                    class="font-['Syne',sans-serif] text-xl font-bold text-foreground tabular-nums"
                >
                    {{ progress.in_progress }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-border/70 bg-background/80 p-3 text-center dark:bg-slate-950/60"
            >
                <div
                    class="flex items-center justify-center gap-1 font-mono text-[10px] text-emerald-600 uppercase dark:text-emerald-400"
                >
                    <CheckCircle2 class="size-3" />
                    <span>Selesai</span>
                </div>
                <p
                    class="font-['Syne',sans-serif] text-xl font-bold text-foreground tabular-nums"
                >
                    {{ progress.completed }}
                </p>
            </div>
        </div>

        <div
            class="mt-4 flex items-center gap-2 border-t border-border/60 pt-3 text-[11px] text-muted-foreground dark:border-border/40"
        >
            <ShieldCheck class="size-4 shrink-0 text-indigo-500" />
            <span
                >Agregasi otomatis diperbarui saat seluruh sub-bagian
                menyelesaikan penugasan.</span
            >
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CalendarClock,
    Crown,
    FileText,
    Sparkles,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    formatRoutingDateTime,
    letterRoutingStatusClass,
    letterRoutingStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { LetterRoutingItem } from '@/types';

defineProps<{
    letter: LetterRoutingItem;
    backHref: string;
    backLabel: string;
    preview?: boolean;
}>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-border/80 bg-gradient-to-br from-card via-card/90 to-muted/40 p-6 shadow-lg shadow-black/5 backdrop-blur-2xl sm:p-8 dark:border-border/60 dark:from-slate-900 dark:via-slate-900/90 dark:to-slate-950/80"
    >
        <!-- Top Row: Back Action & Status Badges -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 pb-5 dark:border-border/40"
        >
            <Link
                :href="backHref"
                class="group inline-flex items-center gap-2 rounded-2xl border border-border/70 bg-background/80 px-4 py-2 text-xs font-bold text-foreground shadow-xs transition-all hover:border-indigo-500 hover:bg-background hover:text-indigo-600 dark:hover:text-indigo-400"
            >
                <ArrowLeft
                    class="size-3.5 transition-transform group-hover:-translate-x-1"
                />
                <span>{{ backLabel }}</span>
            </Link>

            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center rounded-xl bg-indigo-500/10 px-3 py-1 font-mono text-xs font-bold text-indigo-700 dark:text-indigo-300"
                >
                    Agenda #{{ letter.agenda_number }}
                </span>

                <Badge
                    variant="outline"
                    class="px-2.5 py-0.5 font-mono text-xs font-bold"
                    :class="letterRoutingStatusClass(letter.status)"
                >
                    {{ letterRoutingStatusLabels[letter.status] }}
                </Badge>

                <Badge
                    v-if="preview"
                    variant="secondary"
                    class="px-2.5 py-0.5 text-xs font-medium"
                >
                    <Sparkles class="mr-1 size-3 text-amber-500" />
                    <span>Pratinjau Lokal</span>
                </Badge>
            </div>
        </div>

        <!-- Main Heading & Executive Target Summary -->
        <div
            class="mt-5 flex flex-col justify-between gap-6 lg:flex-row lg:items-start"
        >
            <div class="max-w-3xl space-y-3">
                <div
                    class="flex items-center gap-2 text-xs font-bold tracking-wider text-indigo-600 uppercase dark:text-indigo-400"
                >
                    <FileText class="size-4" />
                    <span>Naskah Dinas Masuk · Lembar Disposisi Eksekutif</span>
                </div>

                <h1
                    class="font-['Syne',sans-serif] text-2xl leading-snug font-extrabold tracking-tight text-foreground sm:text-3xl lg:text-4xl"
                >
                    {{ letter.subject }}
                </h1>

                <div
                    class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-1 text-xs text-muted-foreground"
                >
                    <div class="flex items-center gap-1.5">
                        <Building2 class="size-4 text-indigo-500" />
                        <span
                            >Instansi:
                            <strong class="text-foreground">{{
                                letter.sender_organization_name
                            }}</strong></span
                        >
                    </div>

                    <div class="flex items-center gap-1.5 tabular-nums">
                        <CalendarClock class="size-4 text-muted-foreground" />
                        <span
                            >Diterima:
                            <strong class="text-foreground">{{
                                formatRoutingDateTime(letter.received_at)
                            }}</strong></span
                        >
                    </div>
                </div>
            </div>

            <!-- Right: Target Executive Position Box -->
            <div
                v-if="letter.current_route"
                class="flex shrink-0 items-center gap-3.5 rounded-2xl border border-indigo-500/30 bg-indigo-500/10 p-4 text-xs shadow-xs backdrop-blur-md dark:border-indigo-500/20 dark:bg-indigo-950/40"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-xs"
                >
                    <Crown class="size-5" />
                </div>
                <div>
                    <span
                        class="font-mono text-[10px] font-bold tracking-wider text-indigo-700 uppercase dark:text-indigo-300"
                    >
                        Tujuan Eksekutif Resmi
                    </span>
                    <p
                        class="font-['Syne',sans-serif] text-sm font-bold text-foreground"
                    >
                        {{ letter.current_route.target_position.name }}
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        Pejabat:
                        {{ letter.current_route.target_position.holder_name }}
                    </p>
                </div>
            </div>
        </div>
    </header>
</template>

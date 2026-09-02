<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    Clock3,
    FileText,
    GitBranch,
    ShieldCheck,
    UserRoundCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    formatRoutingDateTime,
    formatRoutingFileSize,
    initialRouteStatusClass,
    initialRouteStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { ExecutiveInboxItem } from '@/types';

defineProps<{ routes: ExecutiveInboxItem[] }>();

function getPhaseLabel(phase: string): string {
    switch (phase) {
        case 'AWAITING_DECISION':
            return 'Menunggu Disposisi Pertama';
        case 'AWAITING_FORWARDING':
            return 'Menunggu Penerusan Asisten';
        case 'IN_PROGRESS':
            return 'Sedang Ditangani (Cabang Aktif)';
        case 'COMPLETED':
            return 'Disposisi Selesai';
        default:
            return phase;
    }
}

function getPhaseBadgeClass(phase: string): string {
    switch (phase) {
        case 'AWAITING_DECISION':
            return 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300';
        case 'AWAITING_FORWARDING':
            return 'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300';
        case 'IN_PROGRESS':
            return 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300';
        case 'COMPLETED':
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
        default:
            return 'border-slate-500/30 bg-slate-500/10 text-slate-700 dark:text-slate-300';
    }
}
</script>

<template>
    <div class="grid grid-cols-1 gap-5">
        <article
            v-for="route in routes"
            :key="route.route_id"
            class="group relative overflow-hidden rounded-3xl border border-border/80 bg-card p-6 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-indigo-500/40 hover:shadow-xl dark:border-border/60 dark:bg-slate-900/80"
        >
            <!-- Card Header: Metadata Row -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 pb-4 dark:border-border/40">
                <!-- Left: Agenda & External No -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-xl bg-indigo-500/10 px-3 py-1 font-mono text-xs font-bold text-indigo-700 dark:text-indigo-300">
                        Agenda #{{ route.letter.agenda_number }}
                    </span>

                    <span
                        v-if="route.letter.external_letter_number"
                        class="font-mono text-xs text-muted-foreground"
                    >
                        No. Surat: <strong class="text-foreground">{{ route.letter.external_letter_number }}</strong>
                    </span>
                </div>

                <!-- Right: Phase & Route Status Badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        class="px-2.5 py-0.5 font-mono text-[11px] font-bold"
                        :class="getPhaseBadgeClass(route.branch_progress.phase)"
                    >
                        <span
                            v-if="route.branch_progress.phase === 'AWAITING_DECISION'"
                            class="mr-1.5 size-1.5 rounded-full bg-amber-500 animate-pulse"
                        />
                        <span>{{ getPhaseLabel(route.branch_progress.phase) }}</span>
                    </Badge>

                    <Badge
                        v-if="route.letter.current_route"
                        variant="outline"
                        class="px-2.5 py-0.5 font-mono text-[11px] font-bold"
                        :class="initialRouteStatusClass(route.letter.current_route.status)"
                    >
                        {{ initialRouteStatusLabels[route.letter.current_route.status] }}
                    </Badge>
                </div>
            </div>

            <!-- Card Body: Subject & Core Content -->
            <div class="mt-4 flex flex-col justify-between gap-6 lg:flex-row lg:items-start">
                <div class="space-y-3 lg:max-w-2xl">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400">
                            <FileText class="size-5" />
                        </div>
                        <div>
                            <h2 class="font-['Syne',sans-serif] text-base font-bold leading-snug text-foreground sm:text-lg">
                                {{ route.letter.subject }}
                            </h2>
                            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                <div class="flex items-center gap-1.5">
                                    <Building2 class="size-3.5 text-indigo-500" />
                                    <span class="font-medium text-foreground">{{ route.letter.sender_organization_name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <Clock3 class="size-3.5 text-muted-foreground" />
                                    <span>Masuk Inbox: {{ formatRoutingDateTime(route.received_in_inbox_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Action Button -->
                <div class="flex shrink-0 items-center lg:self-center">
                    <Link
                        :href="route.links.show"
                        class="group/btn inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-700 hover:shadow-indigo-600/30 sm:text-sm"
                    >
                        <span>Telaah & Buat Disposisi</span>
                        <ArrowRight class="size-4 transition-transform group-hover/btn:translate-x-1" />
                    </Link>
                </div>
            </div>

            <!-- Card Footer: Context Pill & Progress Breakdown -->
            <div class="mt-5 grid grid-cols-1 gap-3 border-t border-border/60 pt-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-border/40">
                <!-- Routed By Information -->
                <div
                    v-if="route.letter.current_route"
                    class="flex items-center gap-2.5 rounded-2xl border border-border/60 bg-muted/40 p-3 text-xs"
                >
                    <UserRoundCheck class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <div class="min-w-0">
                        <p class="font-mono text-[10px] text-muted-foreground uppercase">Diarahkan oleh:</p>
                        <p class="font-semibold text-foreground truncate">{{ route.letter.current_route.routed_by.name }}</p>
                        <p class="text-[11px] text-muted-foreground truncate">{{ route.letter.current_route.routed_by.position }}</p>
                    </div>
                </div>

                <!-- Attached Document Snapshot -->
                <div class="flex items-center gap-2.5 rounded-2xl border border-border/60 bg-muted/40 p-3 text-xs">
                    <ShieldCheck class="size-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                    <div class="min-w-0">
                        <p class="font-mono text-[10px] text-muted-foreground uppercase">Dokumen Resmi (SHA-256):</p>
                        <p class="font-semibold text-foreground truncate">
                            {{ route.letter.current_document?.original_filename ?? 'Naskah Digital' }}
                        </p>
                        <p class="font-mono text-[10px] text-muted-foreground">
                            {{ formatRoutingFileSize(route.letter.current_document?.size_bytes ?? 0) }} · Terverifikasi
                        </p>
                    </div>
                </div>

                <!-- Branch Progress (if exists) -->
                <div class="flex items-center gap-2.5 rounded-2xl border border-border/60 bg-muted/40 p-3 text-xs sm:col-span-2 lg:col-span-1">
                    <GitBranch class="size-4 text-sky-600 dark:text-sky-400 shrink-0" />
                    <div class="w-full min-w-0">
                        <div class="flex items-center justify-between text-[11px] font-medium">
                            <span class="font-mono text-[10px] text-muted-foreground uppercase">Progres Tindak Lanjut</span>
                            <span class="font-bold text-foreground">
                                {{ route.branch_progress.completed_branches }}/{{ route.branch_progress.total_branches }} Selesai
                            </span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-500"
                                :style="{ width: `${route.branch_progress.total_branches > 0 ? (route.branch_progress.completed_branches / route.branch_progress.total_branches) * 100 : (route.branch_progress.phase === 'COMPLETED' ? 100 : 0)}%` }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, Clock3, FileText } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatRoutingDateTime,
    initialRouteStatusClass,
    initialRouteStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { ExecutiveInboxItem } from '@/types';

defineProps<{ routes: ExecutiveInboxItem[] }>();

function getPhaseLabel(phase: string): string {
    switch (phase) {
        case 'AWAITING_DECISION':
            return 'Menunggu Disposisi';
        case 'AWAITING_FORWARDING':
            return 'Menunggu Penerusan';
        case 'IN_PROGRESS':
            return 'Sedang Ditangani';
        case 'COMPLETED':
            return 'Selesai';
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
    <div
        class="overflow-x-auto rounded-3xl border border-border/80 bg-card shadow-sm dark:border-border/60 dark:bg-slate-900/80"
    >
        <table class="w-full min-w-5xl text-left text-xs">
            <caption class="sr-only">
                Daftar surat resmi yang diterima pimpinan
            </caption>
            <thead
                class="border-b border-border/70 bg-muted/50 dark:border-border/50"
            >
                <tr
                    class="font-mono text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">
                        Naskah Surat & Agenda
                    </th>
                    <th scope="col" class="px-5 py-3.5">Instansi Pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Diarahkan Oleh</th>
                    <th scope="col" class="px-5 py-3.5">Fase & Status</th>
                    <th scope="col" class="px-5 py-3.5">Waktu Masuk</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60 dark:divide-border/40">
                <tr
                    v-for="route in routes"
                    :key="route.route_id"
                    class="transition-colors duration-150 hover:bg-indigo-500/5 dark:hover:bg-indigo-500/10"
                >
                    <!-- Column 1: Subject & Agenda -->
                    <td class="max-w-xs px-5 py-4 align-top">
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400"
                            >
                                <FileText class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p
                                    class="line-clamp-2 font-semibold text-foreground"
                                >
                                    {{ route.letter.subject }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-[10px] text-muted-foreground"
                                >
                                    #{{ route.letter.agenda_number }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <!-- Column 2: Sender -->
                    <td class="px-5 py-4 align-top">
                        <div class="flex items-start gap-2">
                            <Building2
                                class="mt-0.5 size-3.5 shrink-0 text-indigo-500"
                            />
                            <span class="font-medium text-foreground">
                                {{ route.letter.sender_organization_name }}
                            </span>
                        </div>
                    </td>

                    <!-- Column 3: Routed by -->
                    <td class="px-5 py-4 align-top">
                        <div
                            v-if="route.letter.current_route"
                            class="space-y-0.5"
                        >
                            <p class="font-semibold text-foreground">
                                {{ route.letter.current_route.routed_by.name }}
                            </p>
                            <p class="text-[11px] text-muted-foreground">
                                {{
                                    route.letter.current_route.routed_by
                                        .position
                                }}
                            </p>
                        </div>
                        <span v-else class="text-muted-foreground">-</span>
                    </td>

                    <!-- Column 4: Phase & Status -->
                    <td class="px-5 py-4 align-top">
                        <div class="flex flex-col items-start gap-1.5">
                            <Badge
                                variant="outline"
                                class="px-2 py-0.5 font-mono text-[10px] font-bold"
                                :class="
                                    getPhaseBadgeClass(
                                        route.branch_progress.phase,
                                    )
                                "
                            >
                                {{ getPhaseLabel(route.branch_progress.phase) }}
                            </Badge>

                            <Badge
                                v-if="route.letter.current_route"
                                variant="outline"
                                class="px-2 py-0.5 font-mono text-[10px] font-bold"
                                :class="
                                    initialRouteStatusClass(
                                        route.letter.current_route.status,
                                    )
                                "
                            >
                                {{
                                    initialRouteStatusLabels[
                                        route.letter.current_route.status
                                    ]
                                }}
                            </Badge>
                        </div>
                    </td>

                    <!-- Column 5: Time -->
                    <td class="px-5 py-4 align-top">
                        <div
                            class="flex items-center gap-1.5 font-mono text-[11px] text-muted-foreground"
                        >
                            <Clock3 class="size-3.5" />
                            <span>{{
                                formatRoutingDateTime(
                                    route.received_in_inbox_at,
                                )
                            }}</span>
                        </div>
                    </td>

                    <!-- Column 6: Action -->
                    <td class="px-5 py-4 text-right align-top">
                        <Button
                            as-child
                            size="sm"
                            class="h-9 rounded-xl bg-indigo-600 px-3.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-700"
                        >
                            <Link :href="route.links.show">
                                <span>Buka</span>
                                <ArrowRight class="ml-1 size-3.5" />
                            </Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

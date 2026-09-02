<script setup lang="ts">
import {
    Crown,
    Route as RouteIcon,
    ShieldCheck,
    UserRoundCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    formatRoutingDateTime,
    initialRouteStatusClass,
    initialRouteStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { InitialRouteReceipt } from '@/types';

defineProps<{ route: InitialRouteReceipt }>();
</script>

<template>
    <section class="rounded-3xl border border-border/80 bg-card p-6 shadow-sm dark:border-border/60 dark:bg-slate-900/80">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 pb-4 dark:border-border/40">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400">
                    <RouteIcon class="size-5" />
                </div>
                <div>
                    <h2 class="font-['Syne',sans-serif] text-base font-bold text-foreground">
                        Bukti Routing Awal Kedinasan
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Tercatat permanen pada sistem audit forensik.
                    </p>
                </div>
            </div>

            <Badge
                variant="outline"
                class="px-2.5 py-0.5 font-mono text-[11px] font-bold"
                :class="initialRouteStatusClass(route.status)"
            >
                {{ initialRouteStatusLabels[route.status] }}
            </Badge>
        </div>

        <div class="mt-5 space-y-3.5">
            <!-- Target Pimpinan -->
            <div class="flex items-start gap-3 rounded-2xl border border-indigo-500/25 bg-indigo-500/5 p-4 dark:bg-indigo-950/30">
                <Crown class="mt-0.5 size-5 text-indigo-600 dark:text-indigo-400 shrink-0" />
                <div class="min-w-0">
                    <span class="font-mono text-[10px] font-bold text-indigo-700 dark:text-indigo-300 uppercase">
                        Pimpinan Penerima
                    </span>
                    <p class="font-['Syne',sans-serif] text-sm font-bold text-foreground">
                        {{ route.target_position.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Pejabat: {{ route.target_position.holder_name }}
                    </p>
                </div>
            </div>

            <!-- Routed By -->
            <div class="flex items-start gap-3 rounded-2xl border border-border/70 bg-muted/40 p-4">
                <UserRoundCheck class="mt-0.5 size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <div class="min-w-0 text-xs">
                    <span class="font-mono text-[10px] font-bold text-muted-foreground uppercase">
                        Diarahkan Oleh:
                    </span>
                    <p class="font-bold text-foreground">{{ route.routed_by.name }}</p>
                    <p class="text-muted-foreground">
                        {{ route.routed_by.position }}
                        <template v-if="route.routed_by.unit">· {{ route.routed_by.unit }}</template>
                    </p>
                    <p class="mt-1 font-mono text-[10px] text-muted-foreground tabular-nums">
                        {{ formatRoutingDateTime(route.routed_at) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 rounded-2xl bg-muted/30 p-3 text-[11px] text-muted-foreground">
                <ShieldCheck class="size-4 shrink-0 text-indigo-500" />
                <span>Identitas pencatat dan jabatan historis divalidasi oleh server.</span>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import {
    Crown,
    Route as RouteIcon,
    ShieldCheck,
    UserRoundCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatRoutingDateTime,
    initialRouteStatusClass,
    initialRouteStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { InitialRouteReceipt } from '@/types';

defineProps<{ route: InitialRouteReceipt }>();
</script>

<template>
    <Card class="border-violet-200/80 py-0 shadow-sm dark:border-violet-950">
        <CardHeader class="border-b p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-violet-500/10 text-violet-700 dark:text-violet-300"
                    >
                        <RouteIcon class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <CardTitle>Bukti routing awal</CardTitle>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            Route ini bersifat permanen dan tercatat dalam
                            audit.
                        </p>
                    </div>
                </div>
                <Badge
                    variant="outline"
                    :class="initialRouteStatusClass(route.status)"
                >
                    {{ initialRouteStatusLabels[route.status] }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="grid gap-4 p-5 sm:p-6">
            <div
                class="flex items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/60 p-4 dark:border-violet-900 dark:bg-violet-950/25"
            >
                <Crown
                    class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">Tujuan resmi</p>
                    <p class="mt-1 font-semibold">
                        {{ route.target_position.name }}
                    </p>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ route.target_position.holder_name }}
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3 rounded-2xl bg-muted/50 p-4">
                <UserRoundCheck
                    class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">Diarahkan oleh</p>
                    <p class="mt-1 font-semibold">{{ route.routed_by.name }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ route.routed_by.position }}
                        <template v-if="route.routed_by.unit">
                            · {{ route.routed_by.unit }}
                        </template>
                    </p>
                    <p class="mt-2 text-xs font-medium tabular-nums">
                        {{ formatRoutingDateTime(route.routed_at) }}
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
                Identitas pencatat dan jabatan historis ditetapkan server saat
                route dibuat.
            </p>
        </CardContent>
    </Card>
</template>

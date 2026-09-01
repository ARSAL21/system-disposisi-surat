<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    Clock3,
    FileText,
    UserRoundCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatRoutingDateTime,
    initialRouteStatusClass,
    initialRouteStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { ExecutiveInboxItem } from '@/types';

defineProps<{ routes: ExecutiveInboxItem[] }>();
</script>

<template>
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-6xl text-left text-sm">
            <caption class="sr-only">
                Daftar initial route surat yang diterima pimpinan
            </caption>
            <thead class="border-b bg-slate-50/75 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Surat</th>
                    <th scope="col" class="px-5 py-3.5">Pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Diarahkan oleh</th>
                    <th scope="col" class="px-5 py-3.5">Masuk inbox</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="route in routes"
                    :key="route.route_id"
                    class="transition-colors duration-200 hover:bg-blue-50/45 motion-reduce:transition-none dark:hover:bg-blue-950/15"
                >
                    <td class="max-w-md px-5 py-4 align-top">
                        <div class="flex gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                            >
                                <FileText class="size-4" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="line-clamp-2 leading-5 font-semibold">
                                    {{ route.letter.subject }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-xs text-muted-foreground"
                                >
                                    Agenda {{ route.letter.agenda_number }}
                                </p>
                                <Badge
                                    v-if="route.letter.current_route"
                                    variant="outline"
                                    :class="[
                                        'mt-2',
                                        initialRouteStatusClass(
                                            route.letter.current_route.status,
                                        ),
                                    ]"
                                >
                                    {{
                                        initialRouteStatusLabels[
                                            route.letter.current_route.status
                                        ]
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div class="flex min-w-48 gap-2.5">
                            <Building2
                                class="mt-0.5 size-4 shrink-0 text-violet-600 dark:text-violet-300"
                                aria-hidden="true"
                            />
                            <span class="leading-5 font-medium">
                                {{ route.letter.sender_organization_name }}
                            </span>
                        </div>
                    </td>
                    <td class="min-w-56 px-5 py-4 align-top">
                        <div
                            v-if="route.letter.current_route"
                            class="flex gap-2.5"
                        >
                            <UserRoundCheck
                                class="mt-0.5 size-4 shrink-0 text-emerald-700 dark:text-emerald-300"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="font-semibold">
                                    {{
                                        route.letter.current_route.routed_by
                                            .name
                                    }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        route.letter.current_route.routed_by
                                            .position
                                    }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="min-w-48 px-5 py-4 align-top">
                        <div class="flex gap-2.5">
                            <Clock3
                                class="mt-0.5 size-4 shrink-0 text-blue-600 dark:text-blue-300"
                                aria-hidden="true"
                            />
                            <span class="font-medium tabular-nums">
                                {{
                                    formatRoutingDateTime(
                                        route.received_in_inbox_at,
                                    )
                                }}
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button as-child class="min-h-11">
                            <Link :href="route.links.show">
                                Buka surat
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

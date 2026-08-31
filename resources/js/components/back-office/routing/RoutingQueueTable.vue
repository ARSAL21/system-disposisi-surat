<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarClock,
    Crown,
    FileText,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatRoutingDateTime,
    letterRoutingStatusClass,
    letterRoutingStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { LetterRoutingItem } from '@/types';

defineProps<{ letters: LetterRoutingItem[] }>();
</script>

<template>
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-6xl text-left text-sm">
            <caption class="sr-only">
                Daftar surat resmi yang menunggu atau telah memperoleh routing
                awal
            </caption>
            <thead class="border-b bg-slate-50/75 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Surat</th>
                    <th scope="col" class="px-5 py-3.5">Instansi pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Diterima</th>
                    <th scope="col" class="px-5 py-3.5">Tujuan routing</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="letter in letters"
                    :key="letter.id"
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
                                    {{ letter.subject }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-xs text-muted-foreground"
                                >
                                    Agenda {{ letter.agenda_number }}
                                </p>
                                <Badge
                                    variant="outline"
                                    :class="[
                                        'mt-2',
                                        letterRoutingStatusClass(letter.status),
                                    ]"
                                >
                                    {{
                                        letterRoutingStatusLabels[letter.status]
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
                                {{ letter.sender_organization_name }}
                            </span>
                        </div>
                    </td>
                    <td class="min-w-48 px-5 py-4 align-top">
                        <div class="flex gap-2.5">
                            <CalendarClock
                                class="mt-0.5 size-4 shrink-0 text-blue-600 dark:text-blue-300"
                                aria-hidden="true"
                            />
                            <span class="font-medium tabular-nums">
                                {{ formatRoutingDateTime(letter.received_at) }}
                            </span>
                        </div>
                    </td>
                    <td class="min-w-56 px-5 py-4 align-top">
                        <div
                            v-if="letter.current_route"
                            class="flex items-start gap-2.5"
                        >
                            <Crown
                                class="mt-0.5 size-4 shrink-0 text-violet-600 dark:text-violet-300"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="font-semibold">
                                    {{
                                        letter.current_route.target_position
                                            .name
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs leading-5 text-muted-foreground"
                                >
                                    {{
                                        letter.current_route.target_position
                                            .holder_name
                                    }}
                                </p>
                            </div>
                        </div>
                        <span v-else class="text-sm text-muted-foreground">
                            Belum ditentukan
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button
                            as-child
                            :variant="
                                letter.status === 'REGISTERED'
                                    ? 'default'
                                    : 'outline'
                            "
                            class="min-h-11"
                        >
                            <Link :href="letter.links.show">
                                {{
                                    letter.status === 'REGISTERED'
                                        ? 'Tinjau & routing'
                                        : 'Lihat detail'
                                }}
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

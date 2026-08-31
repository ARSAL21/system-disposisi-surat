<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, Clock3, FileText, Send } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type { DispositionInboxItem } from '@/types';

defineProps<{ dispositions: DispositionInboxItem[] }>();
</script>

<template>
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-6xl text-left text-sm">
            <caption class="sr-only">
                Daftar disposisi yang diterima jabatan Asisten aktif
            </caption>
            <thead class="border-b bg-slate-50/75 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Surat</th>
                    <th scope="col" class="px-5 py-3.5">Pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Instruksi</th>
                    <th scope="col" class="px-5 py-3.5">Diterima</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="disposition in dispositions"
                    :key="disposition.recipient_id"
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
                                    {{ disposition.letter.subject }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-xs text-muted-foreground"
                                >
                                    Agenda
                                    {{ disposition.letter.agenda_number }}
                                </p>
                                <div
                                    class="mt-2 flex items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <Building2
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    <span class="line-clamp-1">
                                        {{
                                            disposition.letter
                                                .sender_organization_name
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="min-w-56 px-5 py-4 align-top">
                        <div class="flex gap-2.5">
                            <Send
                                class="mt-0.5 size-4 shrink-0 text-violet-700 dark:text-violet-300"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="font-semibold">
                                    {{ disposition.sender.name }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ disposition.sender.position }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="max-w-xs px-5 py-4 align-top">
                        <div class="flex flex-wrap gap-1.5">
                            <Badge
                                v-for="instruction in disposition.instructions"
                                :key="instruction.code"
                                variant="secondary"
                                class="font-medium"
                            >
                                {{ instruction.name }}
                            </Badge>
                        </div>
                        <Badge
                            variant="outline"
                            :class="[
                                'mt-2',
                                dispositionRecipientStatusClass(
                                    disposition.status,
                                ),
                            ]"
                        >
                            {{
                                dispositionRecipientStatusLabels[
                                    disposition.status
                                ]
                            }}
                        </Badge>
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
                                        disposition.received_at,
                                    )
                                }}
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button as-child class="min-h-11">
                            <Link :href="disposition.links.show">
                                Buka disposisi
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

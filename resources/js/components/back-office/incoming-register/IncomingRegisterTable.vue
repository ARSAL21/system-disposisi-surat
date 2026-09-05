<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Building2, FileText, Hash } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatIncomingRegisterBytes,
    formatIncomingRegisterDate,
    formatIncomingRegisterDateTime,
    incomingRegisterSourceClass,
    incomingRegisterSourceLabels,
    incomingRegisterStatusClass,
    incomingRegisterStatusLabels,
} from '@/lib/incomingRegisterPresentation';
import type { IncomingRegisterItem } from '@/types';

defineProps<{ letters: IncomingRegisterItem[] }>();
</script>

<template>
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-[78rem] text-left text-sm">
            <caption class="sr-only">
                Daftar resmi surat masuk online dan manual
            </caption>
            <thead class="border-b bg-slate-50/80 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Agenda</th>
                    <th scope="col" class="px-5 py-3.5">Surat & pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Nomor eksternal</th>
                    <th scope="col" class="px-5 py-3.5">Sumber</th>
                    <th scope="col" class="px-5 py-3.5">Status</th>
                    <th scope="col" class="px-5 py-3.5">Dokumen</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="letter in letters"
                    :key="letter.id"
                    class="transition-colors hover:bg-amber-50/45 motion-reduce:transition-none dark:hover:bg-amber-950/15"
                >
                    <td class="min-w-52 px-5 py-4 align-top">
                        <div class="flex gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                            >
                                <Hash class="size-4" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="font-mono text-xs font-semibold">
                                    {{ letter.agenda_number }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-muted-foreground tabular-nums"
                                >
                                    {{
                                        formatIncomingRegisterDateTime(
                                            letter.received_at,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="max-w-md px-5 py-4 align-top">
                        <p class="line-clamp-2 leading-5 font-semibold">
                            {{ letter.subject }}
                        </p>
                        <div
                            class="mt-2 flex items-start gap-2 text-xs text-muted-foreground"
                        >
                            <Building2
                                class="mt-0.5 size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span class="line-clamp-2">
                                {{ letter.sender_organization_name }} ·
                                {{ letter.contact_name }}
                            </span>
                        </div>
                    </td>
                    <td class="min-w-48 px-5 py-4 align-top">
                        <p class="font-medium">
                            {{ letter.external_letter_number || 'Tanpa nomor' }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                formatIncomingRegisterDate(
                                    letter.external_letter_date,
                                )
                            }}
                        </p>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <Badge
                            variant="outline"
                            :class="incomingRegisterSourceClass(letter.source)"
                        >
                            {{ incomingRegisterSourceLabels[letter.source] }}
                        </Badge>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <Badge
                            variant="outline"
                            :class="incomingRegisterStatusClass(letter.status)"
                        >
                            {{ incomingRegisterStatusLabels[letter.status] }}
                        </Badge>
                    </td>
                    <td class="max-w-52 px-5 py-4 align-top">
                        <div v-if="letter.document" class="flex gap-2">
                            <FileText
                                class="mt-0.5 size-4 shrink-0 text-emerald-700 dark:text-emerald-300"
                                aria-hidden="true"
                            />
                            <div class="min-w-0">
                                <p
                                    class="truncate text-xs font-medium"
                                    :title="letter.document.original_filename"
                                >
                                    {{ letter.document.original_filename }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        formatIncomingRegisterBytes(
                                            letter.document.size_bytes,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                        <span v-else class="text-xs text-muted-foreground">
                            Metadata dokumen tidak tersedia
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button
                            v-if="letter.links.document_history"
                            as-child
                            variant="outline"
                            class="min-h-11 rounded-xl"
                        >
                            <Link :href="letter.links.document_history">
                                Lihat surat
                                <ArrowUpRight
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </Link>
                        </Button>
                        <span v-else class="text-xs text-muted-foreground">
                            Akses dokumen tidak tersedia
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, FileCheck2, Layers3 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    documentArchiveStatusClass,
    documentArchiveStatusLabels,
    formatArchiveDateTime,
} from '@/lib/documentArchivePresentation';
import { formatBytes } from '@/lib/documentVersionPreview';
import type { DocumentArchiveItem } from '@/types';

defineProps<{ documents: DocumentArchiveItem[] }>();
</script>

<template>
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-6xl text-left text-sm">
            <caption class="sr-only">
                Daftar surat resmi beserta versi dokumen terkininya
            </caption>
            <thead class="border-b bg-slate-50/75 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Surat</th>
                    <th scope="col" class="px-5 py-3.5">Instansi pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Dokumen terkini</th>
                    <th scope="col" class="px-5 py-3.5">Versi</th>
                    <th scope="col" class="px-5 py-3.5">Pembaruan</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="document in documents"
                    :key="document.id"
                    class="transition-colors hover:bg-blue-50/45 motion-reduce:transition-none dark:hover:bg-blue-950/15"
                >
                    <td class="max-w-sm px-5 py-4 align-top">
                        <div class="flex gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                            >
                                <FileCheck2 class="size-4" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="line-clamp-2 leading-5 font-semibold">
                                    {{ document.subject }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-xs text-muted-foreground"
                                >
                                    Agenda {{ document.agenda_number }}
                                </p>
                                <Badge
                                    variant="outline"
                                    :class="[
                                        'mt-2',
                                        documentArchiveStatusClass(
                                            document.status,
                                        ),
                                    ]"
                                >
                                    {{
                                        documentArchiveStatusLabels[
                                            document.status
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
                                {{ document.sender_organization_name }}
                            </span>
                        </div>
                    </td>
                    <td class="max-w-xs px-5 py-4 align-top">
                        <p
                            class="truncate font-medium"
                            :title="document.current_version.original_filename"
                        >
                            {{ document.current_version.original_filename }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                formatBytes(document.current_version.size_bytes)
                            }}
                            · SHA-256 tercatat
                        </p>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div
                            class="inline-flex items-center gap-2 rounded-xl bg-violet-500/10 px-2.5 py-1.5 text-violet-700 dark:text-violet-300"
                        >
                            <Layers3 class="size-4" aria-hidden="true" />
                            <span class="font-semibold tabular-nums">{{
                                document.total_versions
                            }}</span>
                        </div>
                    </td>
                    <td class="min-w-44 px-5 py-4 align-top">
                        <p class="font-medium tabular-nums">
                            {{
                                formatArchiveDateTime(
                                    document.current_version.created_at,
                                )
                            }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Versi aktif v{{
                                document.current_version.version_number
                            }}
                        </p>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button as-child variant="outline" class="min-h-11">
                            <Link :href="document.links.history">
                                Lihat histori
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

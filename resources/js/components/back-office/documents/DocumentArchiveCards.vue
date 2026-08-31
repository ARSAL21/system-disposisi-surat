<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarClock,
    FileText,
    Layers3,
} from '@lucide/vue';
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
    <div class="grid gap-3 p-3 lg:hidden">
        <article
            v-for="document in documents"
            :key="document.id"
            class="rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Badge
                    variant="outline"
                    :class="documentArchiveStatusClass(document.status)"
                >
                    {{ documentArchiveStatusLabels[document.status] }}
                </Badge>
                <span class="font-mono text-xs text-muted-foreground">
                    {{ document.agenda_number }}
                </span>
            </div>

            <h2 class="mt-4 leading-6 font-semibold">{{ document.subject }}</h2>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex gap-3">
                    <Building2
                        class="mt-0.5 size-4 shrink-0 text-violet-600"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Instansi pengirim
                        </dt>
                        <dd class="mt-1 font-medium">
                            {{ document.sender_organization_name }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <CalendarClock
                        class="mt-0.5 size-4 shrink-0 text-blue-600"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Pembaruan terakhir
                        </dt>
                        <dd class="mt-1 font-medium tabular-nums">
                            {{
                                formatArchiveDateTime(
                                    document.current_version.created_at,
                                )
                            }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3 sm:col-span-2">
                    <FileText
                        class="mt-0.5 size-4 shrink-0 text-blue-600"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <dt class="text-xs text-muted-foreground">
                            Dokumen aktif
                        </dt>
                        <dd class="mt-1 truncate font-medium">
                            {{ document.current_version.original_filename }}
                        </dd>
                        <dd class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                formatBytes(document.current_version.size_bytes)
                            }}
                        </dd>
                    </div>
                </div>
            </dl>

            <div
                class="mt-4 flex items-center justify-between rounded-xl bg-muted/55 px-3 py-2 text-xs"
            >
                <span
                    class="inline-flex items-center gap-2 text-muted-foreground"
                >
                    <Layers3
                        class="size-4 text-violet-600"
                        aria-hidden="true"
                    />
                    {{ document.total_versions }} versi tersimpan
                </span>
                <span class="font-semibold"
                    >v{{ document.current_version.version_number }} aktif</span
                >
            </div>

            <Button as-child variant="outline" class="mt-4 min-h-11 w-full">
                <Link :href="document.links.history">
                    Lihat histori dan metadata
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
        </article>
    </div>
</template>

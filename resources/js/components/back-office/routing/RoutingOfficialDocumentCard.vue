<script setup lang="ts">
import {
    Download,
    Eye,
    FileCheck2,
    Fingerprint,
    ShieldCheck,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatRoutingDateTime,
    formatRoutingFileSize,
} from '@/lib/letterRoutingPresentation';
import type { RoutingOfficialDocument } from '@/types';

defineProps<{
    document: RoutingOfficialDocument;
    preview?: boolean;
}>();

defineEmits<{
    preview: [];
    download: [];
}>();
</script>

<template>
    <Card
        class="overflow-hidden border-blue-200/80 bg-gradient-to-br from-white via-white to-blue-50/55 py-0 shadow-sm dark:border-blue-950 dark:from-slate-950 dark:via-slate-950 dark:to-blue-950/20"
    >
        <CardHeader
            class="border-b border-blue-100 p-5 sm:p-6 dark:border-blue-950"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-700 to-violet-700 text-white shadow-sm"
                    >
                        <FileCheck2 class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <CardTitle>Dokumen resmi terkini</CardTitle>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            Versi ini menjadi acuan tunggal pada saat routing.
                        </p>
                    </div>
                </div>
                <span
                    class="inline-flex w-fit items-center gap-1.5 rounded-full border border-emerald-300/70 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                    <ShieldCheck class="size-3.5" aria-hidden="true" />
                    Versi {{ document.version_number }} terverifikasi
                </span>
            </div>
        </CardHeader>

        <CardContent class="p-5 sm:p-6">
            <p class="font-semibold break-all">
                {{ document.original_filename }}
            </p>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-xl bg-background/80 p-3">
                    <dt class="text-xs text-muted-foreground">Format</dt>
                    <dd class="mt-1 font-semibold">PDF</dd>
                </div>
                <div class="rounded-xl bg-background/80 p-3">
                    <dt class="text-xs text-muted-foreground">Ukuran</dt>
                    <dd class="mt-1 font-semibold tabular-nums">
                        {{ formatRoutingFileSize(document.size_bytes) }}
                    </dd>
                </div>
                <div class="rounded-xl bg-background/80 p-3">
                    <dt class="text-xs text-muted-foreground">Dicatat</dt>
                    <dd class="mt-1 font-semibold tabular-nums">
                        {{ formatRoutingDateTime(document.recorded_at) }}
                    </dd>
                </div>
            </dl>

            <div
                class="mt-4 flex items-start gap-3 rounded-xl border border-dashed p-3"
            >
                <Fingerprint
                    class="mt-0.5 size-4 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground">
                        Fingerprint SHA-256 tercatat
                    </p>
                    <code class="mt-1 block text-xs leading-5 break-all">
                        {{ document.sha256 }}
                    </code>
                </div>
            </div>

            <div class="mt-5 grid gap-2 sm:grid-cols-2">
                <template v-if="preview">
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11"
                        @click="$emit('preview')"
                    >
                        <Eye class="size-4" aria-hidden="true" />
                        Pratinjau PDF
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11"
                        @click="$emit('download')"
                    >
                        <Download class="size-4" aria-hidden="true" />
                        Unduh PDF
                    </Button>
                </template>
                <template v-else>
                    <Button as-child variant="outline" class="min-h-11">
                        <a
                            :href="document.preview_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Eye class="size-4" aria-hidden="true" />
                            Pratinjau PDF
                        </a>
                    </Button>
                    <Button as-child variant="outline" class="min-h-11">
                        <a :href="document.download_url">
                            <Download class="size-4" aria-hidden="true" />
                            Unduh PDF
                        </a>
                    </Button>
                </template>
            </div>
        </CardContent>
    </Card>
</template>

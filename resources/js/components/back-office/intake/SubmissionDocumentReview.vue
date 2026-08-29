<script setup lang="ts">
import { Download, Eye, FileText, LockKeyhole, ShieldCheck } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import type { IntakeDocument } from '@/types';

defineProps<{
    document: IntakeDocument;
    previewUrl: string | null;
    downloadUrl: string | null;
    canDownload: boolean;
    previewMode?: boolean;
}>();
</script>

<template>
    <Card class="overflow-hidden shadow-sm">
        <CardHeader class="border-b">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>Dokumen untuk diperiksa</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">
                        PDF asli tersimpan di ruang penyimpanan terlindungi.
                    </p>
                </div>
                <span
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
                >
                    <ShieldCheck class="size-4" aria-hidden="true" />
                    Sidik digital tersedia
                </span>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <div class="grid lg:grid-cols-[minmax(0,1fr)_17rem]">
                <div
                    class="flex min-h-72 items-center justify-center bg-slate-100 p-6 dark:bg-slate-950/55"
                >
                    <div class="max-w-xs text-center">
                        <span
                            class="mx-auto flex size-16 items-center justify-center rounded-3xl bg-white text-blue-700 shadow-sm dark:bg-slate-900 dark:text-blue-300"
                        >
                            <FileText class="size-8" aria-hidden="true" />
                        </span>
                        <p class="mt-4 font-semibold">
                            Area pratinjau PDF privat
                        </p>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Buka dokumen melalui jalur terlindungi yang
                            memeriksa hak akses dan penugasan jabatan aktif.
                        </p>
                    </div>
                </div>

                <aside class="border-t p-5 lg:border-t-0 lg:border-l">
                    <p class="text-sm font-semibold break-all">
                        {{ document.original_filename }}
                    </p>
                    <dl class="mt-4 space-y-3 text-xs">
                        <div>
                            <dt class="text-muted-foreground">Ukuran</dt>
                            <dd class="mt-1 font-medium">
                                {{ formatFileSize(document.size_bytes) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Diunggah</dt>
                            <dd class="mt-1 font-medium tabular-nums">
                                {{
                                    formatSubmissionDateTime(
                                        document.uploaded_at,
                                    )
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">SHA-256</dt>
                            <dd
                                class="mt-1 font-mono text-[11px] leading-5 break-all"
                            >
                                {{ document.sha256 }}
                            </dd>
                        </div>
                    </dl>
                    <div
                        class="mt-5 grid gap-2"
                        aria-describedby="document-preview-note"
                    >
                        <template v-if="previewMode">
                            <Button
                                type="button"
                                variant="outline"
                                class="min-h-11"
                                disabled
                            >
                                <Eye class="size-4" aria-hidden="true" />
                                Buka PDF
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                class="min-h-11"
                                disabled
                            >
                                <Download class="size-4" aria-hidden="true" />
                                Unduh dokumen
                            </Button>
                        </template>
                        <Button
                            v-else-if="canDownload && previewUrl"
                            as-child
                            variant="outline"
                            class="min-h-11"
                        >
                            <a
                                :href="previewUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Eye class="size-4" aria-hidden="true" />
                                Buka PDF
                            </a>
                        </Button>
                        <Button
                            v-if="!previewMode && canDownload && downloadUrl"
                            as-child
                            variant="ghost"
                            class="min-h-11"
                        >
                            <a :href="downloadUrl">
                                <Download class="size-4" aria-hidden="true" />
                                Unduh dokumen
                            </a>
                        </Button>
                    </div>
                    <p
                        id="document-preview-note"
                        class="mt-3 flex gap-2 text-xs leading-5 text-muted-foreground"
                    >
                        <LockKeyhole
                            class="mt-0.5 size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        Hak akses diperiksa kembali oleh sistem.
                    </p>
                </aside>
            </div>
        </CardContent>
    </Card>
</template>

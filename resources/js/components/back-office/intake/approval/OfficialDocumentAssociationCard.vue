<script setup lang="ts">
import { FileCheck2, Link2, ShieldCheck, TriangleAlert } from '@lucide/vue';
import OfficialDocumentFingerprintPanel from '@/components/back-office/intake/approval/OfficialDocumentFingerprintPanel.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import type { OfficialLetterDocument } from '@/types';

defineProps<{
    document?: OfficialLetterDocument | null;
}>();
</script>

<template>
    <Card
        class="overflow-hidden border-blue-200/80 bg-gradient-to-br from-white via-white to-violet-50/60 shadow-sm dark:border-blue-950 dark:from-slate-950 dark:via-slate-950 dark:to-violet-950/25"
    >
        <CardHeader class="border-b border-blue-100/80 dark:border-blue-950">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600 text-white shadow-sm shadow-blue-500/20"
                    >
                        <FileCheck2 class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <CardTitle>Dokumen resmi surat</CardTitle>
                        <p
                            class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground"
                        >
                            Rekaman dokumen yang dibekukan ketika pengajuan
                            menjadi surat masuk resmi.
                        </p>
                    </div>
                </div>

                <span
                    v-if="document"
                    class="inline-flex w-fit items-center gap-1.5 rounded-full border border-emerald-300/70 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                    <ShieldCheck class="size-3.5" aria-hidden="true" />
                    Versi resmi {{ document.version_number }}
                </span>
            </div>
        </CardHeader>

        <CardContent class="p-5 sm:p-6">
            <div
                v-if="!document"
                class="flex gap-3 rounded-2xl border border-amber-300/70 bg-amber-50/80 p-4 text-amber-950 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100"
                role="status"
            >
                <TriangleAlert
                    class="mt-0.5 size-5 shrink-0"
                    aria-hidden="true"
                />
                <div>
                    <p class="font-semibold">
                        Metadata versi resmi belum tersedia
                    </p>
                    <p class="mt-1 text-sm leading-6 opacity-85">
                        Registrasi telah tercatat, tetapi asosiasi dokumen resmi
                        belum diterima. Jangan menyatakan dokumen sebagai versi
                        resmi sampai data tersinkronisasi.
                    </p>
                </div>
            </div>

            <div v-else class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_1.1fr]">
                <section
                    class="rounded-2xl border bg-background/85 p-4 sm:p-5"
                    aria-labelledby="official-document-metadata"
                >
                    <div class="flex items-center gap-2">
                        <FileCheck2
                            class="size-4 text-blue-700 dark:text-blue-300"
                            aria-hidden="true"
                        />
                        <h3
                            id="official-document-metadata"
                            class="text-sm font-semibold"
                        >
                            Metadata versi resmi
                        </h3>
                    </div>

                    <p class="mt-4 font-semibold break-all">
                        {{ document.original_filename }}
                    </p>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-xl bg-muted/55 p-3">
                            <dt class="text-xs text-muted-foreground">Versi</dt>
                            <dd class="mt-1 font-semibold tabular-nums">
                                {{ document.version_number }}
                            </dd>
                        </div>
                        <div class="rounded-xl bg-muted/55 p-3">
                            <dt class="text-xs text-muted-foreground">
                                Ukuran
                            </dt>
                            <dd class="mt-1 font-semibold tabular-nums">
                                {{ formatFileSize(document.size_bytes) }}
                            </dd>
                        </div>
                    </dl>

                    <div
                        class="mt-3 flex items-start gap-3 rounded-xl border border-dashed p-3"
                    >
                        <Link2
                            class="mt-0.5 size-4 shrink-0 text-violet-700 dark:text-violet-300"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Sumber versi
                            </p>
                            <p class="mt-1 text-sm font-semibold">
                                Dokumen pengajuan
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                Dicatat
                                {{
                                    formatSubmissionDateTime(
                                        document.recorded_at,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <OfficialDocumentFingerprintPanel
                    :fingerprint="document.sha256"
                />
            </div>
        </CardContent>
    </Card>
</template>

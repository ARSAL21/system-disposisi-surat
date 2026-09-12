<script setup lang="ts">
import {
    ExternalLink,
    FileCheck2,
    FileSearch,
    Files,
    Fingerprint,
    LockKeyhole,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OutgoingLetterDetail } from '@/types';

defineProps<{ letter: OutgoingLetterDetail; preview?: boolean }>();
const emit = defineEmits<{
    document: [action: 'preview' | 'download', url: string | null];
}>();

function formatBytes(bytes: number): string {
    return (
        new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(
            bytes / 1_048_576,
        ) + ' MB'
    );
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <Card>
        <CardHeader
            class="flex flex-row items-center justify-between gap-3 pb-3"
            ><CardTitle class="flex items-center gap-2 text-base"
                ><Files class="size-4 text-indigo-600" /> Dokumen final bertanda
                tangan</CardTitle
            ><Badge variant="outline" class="rounded-full"
                >{{ letter.documents.length }} versi</Badge
            ></CardHeader
        >
        <CardContent>
            <div v-if="letter.documents.length" class="space-y-3">
                <article
                    v-for="document in letter.documents"
                    :key="document.public_id"
                    class="rounded-2xl border p-4"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="flex min-w-0 gap-3">
                            <span
                                class="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-500/10 text-rose-600"
                                ><FileCheck2 class="size-5"
                            /></span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">
                                    {{ document.original_filename }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Versi {{ document.version_number }} ·
                                    {{ formatBytes(document.size_bytes) }} ·
                                    {{ formatDate(document.uploaded_at) }}
                                </p>
                                <p
                                    v-if="document.upload_note"
                                    class="mt-2 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ document.upload_note }}
                                </p>
                            </div>
                        </div>
                        <Badge
                            variant="outline"
                            class="shrink-0 rounded-full"
                            :class="
                                document.review_status === 'VERIFIED'
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : document.review_status === 'RETURNED'
                                      ? 'border-amber-200 bg-amber-50 text-amber-700'
                                      : ''
                            "
                            >{{
                                document.review_status === 'VERIFIED'
                                    ? 'Terverifikasi'
                                    : document.review_status === 'RETURNED'
                                      ? 'Dikembalikan'
                                      : 'Menunggu verifikasi'
                            }}</Badge
                        >
                    </div>
                    <div
                        class="mt-4 flex flex-col gap-3 border-t pt-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p
                            class="flex min-w-0 items-center gap-2 font-mono text-[11px] text-muted-foreground"
                        >
                            <Fingerprint class="size-3.5 shrink-0" /><span
                                class="truncate"
                                >SHA-256 {{ document.sha256_fingerprint }}</span
                            >
                        </p>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="
                                    emit(
                                        'document',
                                        'preview',
                                        document.links.preview,
                                    )
                                "
                                ><FileSearch class="size-4" /> Pratinjau</Button
                            ><Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="
                                    emit(
                                        'document',
                                        'download',
                                        document.links.download,
                                    )
                                "
                                ><ExternalLink class="size-4" /> Unduh</Button
                            >
                        </div>
                    </div>
                    <p
                        v-if="document.review_note"
                        class="mt-3 rounded-xl bg-amber-50 p-3 text-xs leading-5 text-amber-900 dark:bg-amber-950/30 dark:text-amber-100"
                    >
                        Catatan pemeriksa: {{ document.review_note }}
                    </p>
                </article>
            </div>
            <div
                v-else
                class="grid min-h-40 place-items-center rounded-2xl border border-dashed bg-muted/15 p-6 text-center"
            >
                <div>
                    <LockKeyhole class="mx-auto size-6 text-muted-foreground" />
                    <p class="mt-3 font-semibold">PDF final belum diunggah</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Dokumen baru dapat diunggah setelah nomor resmi
                        diberikan.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

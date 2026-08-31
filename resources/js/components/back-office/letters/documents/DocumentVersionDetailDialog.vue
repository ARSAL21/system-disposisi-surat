<script setup lang="ts">
import { Copy, Download, ExternalLink, GitCommit, Shield } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    formatBytes,
    formatDateTime,
    getSourceBadge,
} from '@/lib/documentVersionPreview';
import type { DocumentVersionItem } from '@/types';

defineProps<{
    open: boolean;
    document: DocumentVersionItem | null;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    copyHash: [hash: string];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="document" class="sm:max-w-2xl">
            <DialogHeader>
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="document.is_current ? 'default' : 'outline'"
                        class="gap-1 px-2 py-0.5 text-xs font-semibold"
                        :class="
                            document.is_current
                                ? 'bg-primary text-primary-foreground'
                                : 'text-foreground'
                        "
                    >
                        <GitCommit class="size-3" aria-hidden="true" />
                        <span>Versi {{ document.version_number }}</span>
                        <span
                            v-if="document.is_current"
                            class="ml-1 text-[10px] font-bold uppercase"
                        >
                            (Terkini)
                        </span>
                    </Badge>
                    <Badge
                        variant="outline"
                        :class="getSourceBadge(document.source).class"
                    >
                        {{ getSourceBadge(document.source).label }}
                    </Badge>
                </div>
                <DialogTitle
                    class="mt-2 text-lg font-bold tracking-tight sm:text-xl"
                >
                    {{ document.original_filename }}
                </DialogTitle>
                <DialogDescription
                    class="text-xs leading-relaxed text-muted-foreground"
                >
                    Informasi metadata dan sidik jari kriptografi dokumen resmi
                    pada penyimpanan privat.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <!-- SHA-256 Fingerprint Full Container -->
                <div class="space-y-1.5">
                    <div
                        class="flex items-center justify-between text-xs font-medium text-muted-foreground"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 font-semibold text-foreground"
                        >
                            <Shield
                                class="size-3.5 text-primary"
                                aria-hidden="true"
                            />
                            Sidik Jari SHA-256 (Penuh)
                        </span>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline focus:outline-none"
                            @click="emit('copyHash', document.sha256)"
                        >
                            <Copy class="size-3" aria-hidden="true" />
                            <span>Salin ke Papan Klip</span>
                        </button>
                    </div>
                    <div
                        class="rounded-lg border border-border bg-muted/60 p-3 font-mono text-xs leading-relaxed break-all text-foreground select-all"
                    >
                        {{ document.sha256 }}
                    </div>
                </div>

                <!-- Correction Reason (if any) -->
                <div
                    v-if="document.correction_reason"
                    class="rounded-lg border border-amber-200 bg-amber-50/70 p-3.5 text-xs text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <div
                        class="font-semibold text-amber-800 dark:text-amber-300"
                    >
                        Alasan Koreksi Resmi:
                    </div>
                    <p class="mt-1 leading-relaxed whitespace-pre-wrap">
                        {{ document.correction_reason }}
                    </p>
                </div>

                <!-- Metadata Details Grid -->
                <div
                    class="grid grid-cols-1 gap-3 rounded-lg border border-border bg-muted/30 p-3.5 text-xs sm:grid-cols-2"
                >
                    <div class="space-y-1">
                        <span class="text-muted-foreground"
                            >Ukuran Berkas:</span
                        >
                        <p class="font-medium text-foreground">
                            {{ formatBytes(document.size_bytes) }} ({{
                                document.size_bytes.toLocaleString('id-ID')
                            }}
                            bytes)
                        </p>
                    </div>

                    <div class="space-y-1">
                        <span class="text-muted-foreground"
                            >Format & Tipe MIME:</span
                        >
                        <p class="font-medium text-foreground">
                            {{ document.mime_type }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <span class="text-muted-foreground"
                            >Pengunggah berkas:</span
                        >
                        <p class="font-medium text-foreground">
                            {{ document.uploaded_by.name }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            {{
                                document.uploaded_by.position ||
                                'Identitas pengunggah'
                            }}
                            <span v-if="document.uploaded_by.unit"
                                >• {{ document.uploaded_by.unit }}</span
                            >
                        </p>
                    </div>

                    <div class="space-y-1">
                        <span class="text-muted-foreground"
                            >Pencatat resmi:</span
                        >
                        <p class="font-medium text-foreground">
                            {{ document.recorded_by?.name || 'Tidak tersedia' }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            {{
                                document.recorded_by?.position ||
                                'Metadata audit tidak tersedia'
                            }}
                            <span v-if="document.recorded_by?.unit"
                                >â€¢ {{ document.recorded_by.unit }}</span
                            >
                        </p>
                    </div>

                    <div class="space-y-1">
                        <span class="text-muted-foreground"
                            >Waktu Pengunggahan:</span
                        >
                        <p class="font-medium text-foreground">
                            {{ formatDateTime(document.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <DialogFooter
                class="flex flex-wrap items-center justify-between gap-2 border-t border-border pt-4"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-10"
                    @click="emit('update:open', false)"
                >
                    Tutup
                </Button>

                <div class="flex items-center gap-2">
                    <Button
                        as="a"
                        :href="document.preview_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        variant="outline"
                        class="min-h-10 gap-1.5"
                    >
                        <ExternalLink class="size-4" aria-hidden="true" />
                        <span>Pratinjau PDF</span>
                    </Button>
                    <Button
                        as="a"
                        :href="document.download_url"
                        variant="default"
                        class="min-h-10 gap-1.5 bg-primary text-primary-foreground"
                    >
                        <Download class="size-4" aria-hidden="true" />
                        <span>Unduh Dokumen</span>
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

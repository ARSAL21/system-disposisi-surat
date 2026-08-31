<script setup lang="ts">
import {
    Calendar,
    Copy,
    Download,
    Eye,
    ExternalLink,
    FileCheck,
    FileText,
    Info,
    ShieldCheck,
    User,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    formatBytes,
    formatDateTime,
    getSourceBadge,
} from '@/lib/documentVersionPreview';
import type { DocumentVersionItem } from '@/types';

defineProps<{
    document: DocumentVersionItem;
}>();

const emit = defineEmits<{
    viewDetail: [document: DocumentVersionItem];
    copyHash: [hash: string];
}>();
</script>

<template>
    <Card class="border-primary/40 bg-card shadow-sm dark:border-primary/30">
        <CardHeader class="pb-3 sm:pb-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Badge
                        class="gap-1 bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-600"
                    >
                        <ShieldCheck class="size-3.5" aria-hidden="true" />
                        <span
                            >Dokumen Resmi Aktif (v{{
                                document.version_number
                            }})</span
                        >
                    </Badge>
                    <Badge
                        variant="outline"
                        :class="getSourceBadge(document.source).class"
                    >
                        {{ getSourceBadge(document.source).label }}
                    </Badge>
                </div>

                <span class="text-xs text-muted-foreground">
                    Diunggah {{ formatDateTime(document.created_at) }}
                </span>
            </div>

            <CardTitle
                class="mt-2 text-lg font-semibold tracking-tight text-foreground sm:text-xl"
            >
                {{ document.original_filename }}
            </CardTitle>

            <CardDescription
                class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
            >
                <span
                    class="inline-flex items-center gap-1 font-medium text-foreground"
                >
                    <FileText
                        class="size-3.5 text-primary"
                        aria-hidden="true"
                    />
                    {{ formatBytes(document.size_bytes) }}
                </span>
                <span>•</span>
                <span>MIME: {{ document.mime_type }}</span>
            </CardDescription>
        </CardHeader>

        <CardContent class="space-y-4 pt-0">
            <!-- SHA-256 Fingerprint Display Box -->
            <div class="space-y-1.5">
                <div
                    class="flex items-center justify-between text-xs font-medium text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-1">
                        <FileCheck
                            class="size-3.5 text-primary"
                            aria-hidden="true"
                        />
                        Sidik Jari Kriptografi SHA-256
                    </span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-xs font-medium text-primary transition-colors hover:text-primary/80 focus:underline focus:outline-none"
                        @click="emit('copyHash', document.sha256)"
                    >
                        <Copy class="size-3" aria-hidden="true" />
                        <span>Salin Hash</span>
                    </button>
                </div>
                <div
                    class="group relative flex items-center justify-between rounded-lg border border-border/80 bg-muted/60 px-3 py-2 font-mono text-xs text-foreground transition-colors hover:border-primary/50"
                >
                    <span
                        class="font-mono leading-relaxed break-all select-all"
                    >
                        {{ document.sha256 }}
                    </span>
                </div>
            </div>

            <!-- Correction reason if present -->
            <div
                v-if="document.correction_reason"
                class="rounded-lg border border-amber-200/80 bg-amber-50/70 p-3.5 text-xs text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
            >
                <div class="font-semibold text-amber-800 dark:text-amber-300">
                    Alasan Koreksi Dokumen:
                </div>
                <p class="mt-1 leading-relaxed whitespace-pre-wrap">
                    {{ document.correction_reason }}
                </p>
            </div>

            <!-- Metadata info grid -->
            <div
                class="grid grid-cols-1 gap-3 rounded-lg border border-border/60 bg-muted/30 p-3 sm:grid-cols-3"
            >
                <div class="space-y-1 text-xs">
                    <span class="text-muted-foreground"
                        >Pengunggah berkas:</span
                    >
                    <div
                        class="flex items-center gap-1.5 font-medium text-foreground"
                    >
                        <User
                            class="size-3.5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span>{{ document.uploaded_by.name }}</span>
                    </div>
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

                <div class="space-y-1 text-xs">
                    <span class="text-muted-foreground">Pencatat resmi:</span>
                    <div
                        class="flex items-center gap-1.5 font-medium text-foreground"
                    >
                        <User
                            class="size-3.5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span>{{
                            document.recorded_by?.name || 'Tidak tersedia'
                        }}</span>
                    </div>
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

                <div class="space-y-1 text-xs">
                    <span class="text-muted-foreground">Waktu Pencatatan:</span>
                    <div
                        class="flex items-center gap-1.5 font-medium text-foreground"
                    >
                        <Calendar
                            class="size-3.5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span>{{ formatDateTime(document.created_at) }}</span>
                    </div>
                    <p
                        class="text-[11px] text-emerald-600 dark:text-emerald-400"
                    >
                        Status: Dokumen resmi terkini
                    </p>
                </div>
            </div>

            <!-- Notice box -->
            <div
                class="flex items-start gap-2 rounded-lg border border-border/60 bg-background/80 p-2.5 text-xs text-muted-foreground"
            >
                <Info
                    class="mt-0.5 size-3.5 shrink-0 text-primary"
                    aria-hidden="true"
                />
                <span class="leading-relaxed">
                    Dokumen ini adalah versi acuan resmi saat ini. Jika terdapat
                    kesalahan administratif, Kepala Bagian Umum dapat mengunggah
                    versi koreksi baru tanpa menghapus riwayat ini.
                </span>
            </div>
        </CardContent>

        <CardFooter
            class="flex flex-col gap-2 border-t border-border/60 bg-muted/20 pt-3 pb-3 sm:flex-row sm:items-center sm:justify-between sm:px-6"
        >
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="w-full gap-1.5 text-xs sm:w-auto"
                @click="emit('viewDetail', document)"
            >
                <Eye class="size-3.5" aria-hidden="true" />
                <span>Detail Metadata</span>
            </Button>

            <div
                class="grid w-full grid-cols-1 gap-2 sm:flex sm:w-auto sm:items-center"
            >
                <Button
                    as="a"
                    :href="document.preview_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    variant="outline"
                    size="sm"
                    class="gap-1.5 text-xs"
                >
                    <ExternalLink class="size-3.5" aria-hidden="true" />
                    <span>Buka Pratinjau</span>
                </Button>
                <Button
                    as="a"
                    :href="document.download_url"
                    variant="default"
                    size="sm"
                    class="gap-1.5 bg-primary text-xs text-primary-foreground"
                >
                    <Download class="size-3.5" aria-hidden="true" />
                    <span>Unduh Berkas</span>
                </Button>
            </div>
        </CardFooter>
    </Card>
</template>

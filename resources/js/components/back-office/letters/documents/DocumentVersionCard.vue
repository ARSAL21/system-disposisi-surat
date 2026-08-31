<script setup lang="ts">
import {
    Copy,
    Download,
    Eye,
    ExternalLink,
    FileText,
    GitCommit,
    Shield,
    User,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import {
    formatBytes,
    formatDateTime,
    formatShortHash,
    getSourceBadge,
} from '@/lib/documentVersionPreview';
import type { DocumentVersionItem } from '@/types';

defineProps<{
    document: DocumentVersionItem;
    isCurrent?: boolean;
}>();

const emit = defineEmits<{
    viewDetail: [document: DocumentVersionItem];
    copyHash: [hash: string];
}>();
</script>

<template>
    <Card
        class="transition-all duration-200"
        :class="[
            isCurrent
                ? 'border-primary/50 bg-card shadow-sm ring-1 ring-primary/20'
                : 'border-border/80 bg-card/70 hover:border-border hover:bg-card',
        ]"
    >
        <CardHeader class="p-4 pb-2 sm:p-5 sm:pb-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        :variant="isCurrent ? 'default' : 'outline'"
                        class="gap-1 px-2.5 py-0.5 text-xs font-semibold"
                        :class="
                            isCurrent
                                ? 'bg-primary text-primary-foreground'
                                : 'text-foreground'
                        "
                    >
                        <GitCommit class="size-3" aria-hidden="true" />
                        <span>Versi {{ document.version_number }}</span>
                        <span
                            v-if="isCurrent"
                            class="ml-1 text-[10px] font-bold tracking-wider uppercase"
                        >
                            (Terkini)
                        </span>
                    </Badge>

                    <Badge
                        v-if="document.replaces_version_number"
                        variant="secondary"
                        class="text-[11px] font-normal"
                    >
                        Menggantikan v{{ document.replaces_version_number }}
                    </Badge>

                    <Badge
                        variant="outline"
                        :class="getSourceBadge(document.source).class"
                    >
                        {{ getSourceBadge(document.source).label }}
                    </Badge>
                </div>

                <span class="text-xs text-muted-foreground">
                    {{ formatDateTime(document.created_at) }}
                </span>
            </div>

            <div class="mt-2.5 flex items-start gap-2.5">
                <div class="mt-0.5 rounded-md bg-muted p-1.5 text-primary">
                    <FileText class="size-4" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1">
                    <h3
                        class="truncate text-sm font-semibold text-foreground sm:text-base"
                    >
                        {{ document.original_filename }}
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        {{ formatBytes(document.size_bytes) }} •
                        {{ document.mime_type }}
                    </p>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-3 p-4 pt-0 sm:p-5 sm:pt-0">
            <!-- Correction Reason Quote -->
            <div
                v-if="document.correction_reason"
                class="rounded-lg border border-amber-200/70 bg-amber-50/50 p-3 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200"
            >
                <span class="font-semibold text-amber-800 dark:text-amber-300"
                    >Catatan Koreksi:</span
                >
                <p
                    class="mt-0.5 leading-relaxed whitespace-pre-wrap text-muted-foreground dark:text-amber-200/90"
                >
                    {{ document.correction_reason }}
                </p>
            </div>

            <!-- Metadata info row -->
            <div
                class="grid gap-2 border-t border-border/50 pt-2 text-xs text-muted-foreground sm:grid-cols-2"
            >
                <div class="flex items-center gap-1.5">
                    <User class="size-3.5" aria-hidden="true" />
                    <span
                        >Diunggah oleh:
                        <strong class="text-foreground">{{
                            document.uploaded_by.name
                        }}</strong>
                        ({{
                            document.uploaded_by.position || 'Pengunggah'
                        }})</span
                    >
                </div>

                <div
                    v-if="document.recorded_by"
                    class="flex items-center gap-1.5"
                >
                    <User class="size-3.5" aria-hidden="true" />
                    <span
                        >Dicatat oleh:
                        <strong class="text-foreground">{{
                            document.recorded_by.name
                        }}</strong>
                        ({{
                            document.recorded_by.position || 'Internal'
                        }})</span
                    >
                </div>

                <div
                    class="flex items-center gap-1.5 font-mono text-[11px] sm:col-span-2"
                >
                    <Shield class="size-3.5 text-primary" aria-hidden="true" />
                    <span>SHA-256:</span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-primary hover:underline"
                        title="Klik untuk menyalin SHA-256 penuh"
                        @click="emit('copyHash', document.sha256)"
                    >
                        <span>{{ formatShortHash(document.sha256) }}</span>
                        <Copy class="size-2.5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </CardContent>

        <CardFooter
            class="flex items-center justify-between border-t border-border/50 bg-muted/10 px-4 py-2.5 sm:px-5"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="h-8 gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                @click="emit('viewDetail', document)"
            >
                <Eye class="size-3.5" aria-hidden="true" />
                <span>Detail</span>
            </Button>

            <div class="flex items-center gap-1.5">
                <Button
                    as="a"
                    :href="document.preview_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1 text-xs"
                >
                    <ExternalLink class="size-3" aria-hidden="true" />
                    <span>Pratinjau</span>
                </Button>
                <Button
                    as="a"
                    :href="document.download_url"
                    variant="secondary"
                    size="sm"
                    class="h-8 gap-1 text-xs"
                >
                    <Download class="size-3" aria-hidden="true" />
                    <span>Unduh</span>
                </Button>
            </div>
        </CardFooter>
    </Card>
</template>

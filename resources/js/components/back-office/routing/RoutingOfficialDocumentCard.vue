<script setup lang="ts">
import {
    Check,
    Copy,
    Download,
    Eye,
    FileCheck2,
    Fingerprint,
    ShieldCheck,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    formatRoutingDateTime,
    formatRoutingFileSize,
} from '@/lib/letterRoutingPresentation';
import type { RoutingOfficialDocument } from '@/types';

const props = defineProps<{
    document: RoutingOfficialDocument;
    preview?: boolean;
}>();

defineEmits<{
    preview: [];
    download: [];
}>();

const isCopied = ref(false);
function copyHash() {
    navigator.clipboard.writeText(props.document.sha256);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
}
</script>

<template>
    <section class="rounded-3xl border border-indigo-500/30 bg-card p-6 shadow-sm dark:border-indigo-500/20 dark:bg-slate-900/80">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 pb-4 dark:border-border/40">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400">
                    <FileCheck2 class="size-5" />
                </div>
                <div>
                    <h2 class="font-['Syne',sans-serif] text-base font-bold text-foreground sm:text-lg">
                        Berkas Naskah Dinas Resmi
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Dokumen acuan tunggal yang terikat dengan sidik jari kriptografis.
                    </p>
                </div>
            </div>

            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 font-mono text-[11px] font-bold text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-300"
            >
                <ShieldCheck class="size-3.5 text-emerald-500" />
                <span>Versi {{ document.version_number }} Terverifikasi</span>
            </span>
        </div>

        <!-- File Metadata Grid -->
        <div class="mt-5 space-y-4">
            <div class="rounded-2xl border border-border/70 bg-background/80 p-4 dark:bg-slate-950/60">
                <p class="text-xs font-bold text-foreground break-all">
                    {{ document.original_filename }}
                </p>

                <div class="mt-3 grid grid-cols-3 gap-2 border-t border-border/60 pt-3 text-xs dark:border-border/40">
                    <div>
                        <span class="font-mono text-[10px] text-muted-foreground uppercase">Format</span>
                        <p class="font-semibold text-foreground">PDF Dinas</p>
                    </div>
                    <div>
                        <span class="font-mono text-[10px] text-muted-foreground uppercase">Ukuran Berkas</span>
                        <p class="font-semibold text-foreground tabular-nums">
                            {{ formatRoutingFileSize(document.size_bytes) }}
                        </p>
                    </div>
                    <div>
                        <span class="font-mono text-[10px] text-muted-foreground uppercase">Direkam Pada</span>
                        <p class="font-semibold text-foreground tabular-nums">
                            {{ formatRoutingDateTime(document.recorded_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- SHA-256 Fingerprint Block with Copy Button -->
            <div class="flex items-center justify-between gap-3 rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-3.5 text-xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <Fingerprint class="size-5 text-indigo-600 dark:text-indigo-400 shrink-0" />
                    <div class="min-w-0">
                        <span class="font-mono text-[10px] font-bold text-muted-foreground uppercase">
                            Sidik Jari Digital SHA-256:
                        </span>
                        <code class="block font-mono text-[10px] text-indigo-600 dark:text-indigo-400 truncate">
                            {{ document.sha256 }}
                        </code>
                    </div>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-1 shrink-0 rounded-xl bg-indigo-600/10 px-2.5 py-1 font-mono text-[10px] font-bold text-indigo-700 transition-colors hover:bg-indigo-600/20 dark:text-indigo-300"
                    @click="copyHash"
                >
                    <Check v-if="isCopied" class="size-3 text-emerald-500" />
                    <Copy v-else class="size-3" />
                    <span>{{ isCopied ? 'Tersalin' : 'Salin Hash' }}</span>
                </button>
            </div>

            <!-- Document Actions: Preview & Download -->
            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 pt-1">
                <template v-if="preview">
                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 rounded-2xl border-border/80 text-xs font-bold"
                        @click="$emit('preview')"
                    >
                        <Eye class="mr-2 size-4 text-indigo-600 dark:text-indigo-400" />
                        <span>Pratinjau Naskah PDF</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 rounded-2xl border-border/80 text-xs font-bold"
                        @click="$emit('download')"
                    >
                        <Download class="mr-2 size-4 text-indigo-600 dark:text-indigo-400" />
                        <span>Unduh Berkas Asli</span>
                    </Button>
                </template>
                <template v-else>
                    <Button
                        as-child
                        variant="outline"
                        class="h-11 rounded-2xl border-border/80 text-xs font-bold"
                    >
                        <a
                            :href="document.preview_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Eye class="mr-2 size-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Pratinjau Naskah PDF</span>
                        </a>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="h-11 rounded-2xl border-border/80 text-xs font-bold"
                    >
                        <a :href="document.download_url">
                            <Download class="mr-2 size-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Unduh Berkas Asli</span>
                        </a>
                    </Button>
                </template>
            </div>
        </div>
    </section>
</template>

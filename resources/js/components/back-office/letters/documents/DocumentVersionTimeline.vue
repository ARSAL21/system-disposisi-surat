<script setup lang="ts">
import { ArrowDown, Diff, History, Info, Shield } from '@lucide/vue';
import DocumentVersionCard from '@/components/back-office/letters/documents/DocumentVersionCard.vue';
import { formatBytes, formatShortHash } from '@/lib/documentVersionPreview';
import type { DocumentVersionItem } from '@/types';

defineProps<{
    versions: DocumentVersionItem[];
}>();

const emit = defineEmits<{
    viewDetail: [document: DocumentVersionItem];
    copyHash: [hash: string];
}>();

function calculateSizeDiff(
    currentBytes: number,
    previousBytes: number,
): { text: string; isIncrease: boolean } {
    const diff = currentBytes - previousBytes;

    if (diff === 0) {
        return { text: 'Ukuran sama (0 Bytes)', isIncrease: false };
    }

    const isIncrease = diff > 0;
    const formatted = formatBytes(Math.abs(diff));

    return {
        text: `${isIncrease ? '+' : '-'}${formatted}`,
        isIncrease,
    };
}
</script>

<template>
    <section class="space-y-4" aria-label="Rantai histori versi dokumen">
        <div
            class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-2">
                <History class="size-4 text-primary" aria-hidden="true" />
                <h2
                    class="text-base font-semibold tracking-tight text-foreground sm:text-lg"
                >
                    Rantai Histori Versi Dokumen
                </h2>
            </div>

            <span class="text-xs text-muted-foreground">
                Urutan kronologis (terkini ke terlama)
            </span>
        </div>

        <!-- Invariant Disclaimer -->
        <div
            class="flex items-start gap-2.5 rounded-lg border border-border/70 bg-muted/40 p-3 text-xs text-muted-foreground"
        >
            <Info
                class="mt-0.5 size-4 shrink-0 text-primary"
                aria-hidden="true"
            />
            <div class="leading-relaxed">
                <strong>Prinsip Immutabilitas Dokumen:</strong> Seluruh berkas
                PDF versi terdahulu tetap tersimpan utuh di storage privat dan
                dapat diunduh kapan saja untuk kebutuhan pembuktian dan audit.
                Perbandingan di bawah didasarkan pada metadata kriptografi
                resmi.
            </div>
        </div>

        <!-- Timeline container -->
        <div class="relative space-y-6 pt-2 pl-4 sm:pl-6">
            <!-- Timeline vertical connector line -->
            <div
                class="absolute top-4 bottom-4 left-6 -ml-px w-0.5 bg-border/80 sm:left-8"
                aria-hidden="true"
            />

            <div
                v-for="(version, index) in versions"
                :key="version.id"
                class="relative flex flex-col gap-4"
            >
                <!-- Timeline Node Badge -->
                <div class="relative flex items-center gap-3">
                    <div
                        class="relative z-10 flex size-5 items-center justify-center rounded-full border-2 bg-background text-[11px] font-bold shadow-xs sm:size-6"
                        :class="[
                            version.is_current
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border text-muted-foreground',
                        ]"
                    >
                        {{ version.version_number }}
                    </div>

                    <span class="text-xs font-semibold text-foreground">
                        Versi {{ version.version_number }}
                        <span
                            v-if="version.is_current"
                            class="font-normal text-emerald-600 dark:text-emerald-400"
                        >
                            • Aktif Sekarang
                        </span>
                    </span>
                </div>

                <!-- Version Card Component -->
                <div class="ml-8 sm:ml-9">
                    <DocumentVersionCard
                        :document="version"
                        :is-current="version.is_current"
                        @view-detail="emit('viewDetail', $event)"
                        @copy-hash="emit('copyHash', $event)"
                    />
                </div>

                <!-- Metadata Difference Callout to Older Version (if exists) -->
                <div
                    v-if="index < versions.length - 1"
                    class="my-1 ml-8 rounded-lg border border-dashed border-border/90 bg-muted/20 p-3 text-xs sm:ml-9"
                >
                    <div
                        class="flex items-center gap-2 font-medium text-foreground"
                    >
                        <Diff
                            class="size-3.5 text-primary"
                            aria-hidden="true"
                        />
                        <span
                            >Perbandingan dengan Versi
                            {{ versions[index + 1].version_number }}:</span
                        >
                    </div>

                    <div
                        class="mt-2 grid grid-cols-1 gap-2 text-[11px] text-muted-foreground sm:grid-cols-2"
                    >
                        <div class="flex items-center gap-1.5 font-mono">
                            <Shield
                                class="size-3 shrink-0 text-primary"
                                aria-hidden="true"
                            />
                            <span>Hash:</span>
                            <span class="truncate">{{
                                formatShortHash(versions[index + 1].sha256)
                            }}</span>
                            <ArrowDown
                                class="size-3 shrink-0 rotate-270 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <span
                                class="truncate font-semibold text-foreground"
                                >{{ formatShortHash(version.sha256) }}</span
                            >
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span>Ukuran berkas:</span>
                            <span
                                class="font-semibold"
                                :class="
                                    calculateSizeDiff(
                                        version.size_bytes,
                                        versions[index + 1].size_bytes,
                                    ).isIncrease
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-emerald-600 dark:text-emerald-400'
                                "
                            >
                                {{
                                    calculateSizeDiff(
                                        version.size_bytes,
                                        versions[index + 1].size_bytes,
                                    ).text
                                }}
                            </span>
                            <span
                                >({{
                                    formatBytes(versions[index + 1].size_bytes)
                                }}
                                → {{ formatBytes(version.size_bytes) }})</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

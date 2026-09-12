<script setup lang="ts">
import { Download, Eye, Fingerprint, FileText, ScanLine } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { SekdaApprovalDetail } from '@/types';

const props = withDefaults(
    defineProps<{ approval: SekdaApprovalDetail; preview?: boolean }>(),
    { preview: false },
);

function openDocument(
    url: string | null,
    action: 'preview' | 'download',
): void {
    if (props.preview) {
        toast.info(
            action === 'preview'
                ? 'Pratinjau PDF akan menggunakan endpoint privat pada produksi.'
                : 'Unduhan fixture dinonaktifkan.',
        );

        return;
    }

    if (url) {
        window.location.assign(url);
    }
}

function percentage(value: number): string {
    return `${Math.round(value * 100)}%`;
}
</script>

<template>
    <Card>
        <CardHeader
            class="flex flex-row items-start justify-between gap-4 border-b pb-4"
            ><div>
                <CardTitle class="flex items-center gap-2 text-base"
                    ><FileText class="size-4 text-indigo-600" /> PDF bernomor
                    untuk disahkan</CardTitle
                >
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    Periksa PDF dan sidik jari sebelum memilih cara pengesahan.
                </p>
            </div>
            <Badge variant="outline" class="rounded-full">{{
                approval.current_document
                    ? `Versi ${approval.current_document.version_number}`
                    : 'Dokumen tidak tersedia'
            }}</Badge></CardHeader
        >
        <CardContent class="p-5 sm:p-6">
            <template v-if="approval.current_document">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_15rem]">
                    <div class="rounded-2xl border bg-muted/20 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span
                                class="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                >Sidik jari PDF</span
                            ><Fingerprint class="size-4 text-indigo-600" />
                        </div>
                        <code
                            class="mt-3 block rounded-xl bg-background p-3 text-xs leading-5 break-all text-foreground"
                            >{{
                                approval.current_document.sha256_fingerprint
                            }}</code
                        >
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="rounded-xl"
                                @click="
                                    openDocument(
                                        approval.current_document
                                            ?.preview_url ?? null,
                                        'preview',
                                    )
                                "
                                ><Eye class="size-4" />Pratinjau</Button
                            ><Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="rounded-xl"
                                @click="
                                    openDocument(
                                        approval.current_document
                                            ?.download_url ?? null,
                                        'download',
                                    )
                                "
                                ><Download class="size-4" />Unduh</Button
                            >
                        </div>
                    </div>
                    <div class="rounded-2xl border bg-background p-4">
                        <p
                            class="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                        >
                            Letak QR
                        </p>
                        <div
                            class="relative mt-3 aspect-[210/297] overflow-hidden rounded-lg border bg-slate-50 shadow-inner dark:bg-slate-950"
                        >
                            <span
                                class="absolute inset-x-4 top-4 h-1.5 rounded bg-slate-200 dark:bg-slate-800"
                            /><span
                                class="absolute inset-x-4 top-8 h-1 rounded bg-slate-200 dark:bg-slate-800"
                            /><span
                                class="absolute inset-x-4 top-12 h-1 rounded bg-slate-200 dark:bg-slate-800"
                            /><span
                                class="absolute grid place-items-center border-2 border-dashed border-indigo-500 bg-indigo-100/90 text-[8px] font-bold text-indigo-700 dark:bg-indigo-950/90 dark:text-indigo-200"
                                :style="{
                                    left: percentage(
                                        approval.qr_placement.x_ratio,
                                    ),
                                    top: percentage(
                                        approval.qr_placement.y_ratio,
                                    ),
                                    width: percentage(
                                        approval.qr_placement.width_ratio,
                                    ),
                                    height: percentage(
                                        approval.qr_placement.height_ratio,
                                    ),
                                }"
                                ><ScanLine class="size-3" /><span
                                    >QR</span
                                ></span
                            >
                        </div>
                        <p class="mt-3 text-xs leading-5 text-muted-foreground">
                            {{ approval.qr_placement.page_label }} · posisi
                            template terkunci
                        </p>
                    </div>
                </div>
            </template>
            <div
                v-else
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100"
            >
                PDF bernomor tidak dapat diverifikasi. Minta unit asal
                mengunggah ulang hasil ekspor PDF yang kompatibel.
            </div>
        </CardContent>
    </Card>
</template>

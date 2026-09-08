<script setup lang="ts">
import { ArrowDownToLine, Check, Clock3, Eye, FileCheck2, LoaderCircle, Send } from '@lucide/vue';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { PublicResponseStatus, PublicResponseTracker } from '@/types';

const props = defineProps<{ tracker: PublicResponseTracker; preview?: boolean }>();

const stages: Array<{ status: PublicResponseStatus; label: string; description: string }> = [
    { status: 'IN_PROCESS', label: 'Sedang diproses', description: 'Surat ditangani oleh unit terkait.' },
    { status: 'PREPARING_RESPONSE', label: 'Balasan disiapkan', description: 'Dokumen resmi sedang diterbitkan.' },
    { status: 'RESPONSE_AVAILABLE', label: 'Balasan tersedia', description: 'Surat balasan dapat diunduh.' },
];

const activeIndex = computed(() => stages.findIndex((stage) => stage.status === props.tracker.status));

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value));
}

function download(url: string): void {
    if (props.preview) {
        toast.info('Unduhan fixture dinonaktifkan. Backend hanya akan melayani pemilik submission.');

        return;
    }

    window.location.assign(url);
}

function previewResponse(url: string): void {
    if (props.preview) {
        toast.info('Pratinjau fixture dinonaktifkan. Backend hanya akan melayani pemilik submission.');

        return;
    }

    window.open(url, '_blank', 'noopener,noreferrer');
}
</script>

<template>
    <section class="overflow-hidden rounded-3xl border bg-card shadow-sm" aria-labelledby="public-response-heading">
        <header class="flex flex-col gap-3 border-b bg-gradient-to-r from-emerald-50/70 via-background to-background p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:from-emerald-950/25">
            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Balasan resmi kantor</p><h2 id="public-response-heading" class="mt-1 text-lg font-semibold">Perkembangan setelah disposisi</h2><p class="mt-1 text-sm text-muted-foreground">Anda tidak perlu menghubungi tiap Bagian untuk mengetahui hasil akhirnya.</p></div>
            <Badge variant="outline" class="w-fit rounded-full border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"><Clock3 class="mr-1 size-3.5" />Diperbarui {{ formatDate(tracker.updated_at) }}</Badge>
        </header>

        <div class="p-5 sm:p-6">
            <ol class="grid gap-0 md:grid-cols-3">
                <li v-for="(stage, index) in stages" :key="stage.status" class="relative flex gap-3 pb-5 last:pb-0 md:block md:pb-0 md:text-center">
                    <span v-if="index < stages.length - 1" class="absolute top-9 bottom-0 left-4 w-0.5 md:top-4 md:right-0 md:bottom-auto md:left-1/2 md:h-0.5 md:w-full" :class="index < activeIndex ? 'bg-emerald-500' : 'bg-border'" aria-hidden="true" />
                    <span class="relative z-10 grid size-9 shrink-0 place-items-center rounded-full border-2 bg-background" :class="index < activeIndex ? 'border-emerald-500 bg-emerald-500 text-white' : index === activeIndex ? 'border-emerald-600 text-emerald-700 ring-4 ring-emerald-500/10 dark:text-emerald-300' : 'border-border text-muted-foreground'"><Check v-if="index < activeIndex" class="size-4" /><FileCheck2 v-else-if="stage.status === 'RESPONSE_AVAILABLE'" class="size-4" /><LoaderCircle v-else class="size-4" /></span>
                    <div class="md:mt-3 md:px-3"><p class="text-sm font-semibold">{{ stage.label }}</p><p class="mt-1 text-xs leading-5 text-muted-foreground">{{ stage.description }}</p></div>
                </li>
            </ol>

            <div v-if="tracker.responses.length" class="mt-7 grid gap-3 border-t pt-5">
                <article v-for="response in tracker.responses" :key="response.public_id" class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 sm:flex-row sm:items-center dark:border-emerald-900 dark:bg-emerald-950/20">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-emerald-600 text-white"><Send class="size-5" /></span>
                    <div class="min-w-0 flex-1"><p class="font-semibold leading-snug">{{ response.subject }}</p><p class="mt-1 font-mono text-xs text-muted-foreground">{{ response.outgoing_number }} · {{ formatDate(response.letter_date) }}</p><p class="mt-1 text-xs text-muted-foreground">Ditandatangani {{ response.signatory_position }}</p></div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <Button type="button" variant="outline" class="min-h-11 rounded-xl" @click="previewResponse(response.preview_url)"><Eye class="size-4" /> Pratinjau</Button>
                        <Button type="button" class="min-h-11 rounded-xl" @click="download(response.download_url)"><ArrowDownToLine class="size-4" /> Unduh balasan</Button>
                    </div>
                </article>
            </div>
            <p v-else class="mt-7 rounded-2xl border border-dashed bg-muted/20 p-5 text-center text-sm leading-6 text-muted-foreground">Belum ada dokumen yang dapat diunduh. Balasan baru muncul setelah diverifikasi dan dikirim oleh Bagian Umum.</p>
        </div>
    </section>
</template>

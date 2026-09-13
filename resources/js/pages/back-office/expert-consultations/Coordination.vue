<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, Eye, Info, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import ExpertConsultationStatusBadge from '@/components/back-office/expert-consultations/ExpertConsultationStatusBadge.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { previewExpertConsultations } from '@/lib/expertConsultationPreview';
import type { ExpertConsultation, ExpertConsultationPageProps } from '@/types';

const props = defineProps<ExpertConsultationPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Koordinasi Telaah', href: '/back-office/expert-consultations/coordination' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const consultations = computed<ExpertConsultation[]>(() =>
    previewMode.value ? previewExpertConsultations : (props.consultations ?? []),
);
</script>

<template>
    <Head title="Koordinasi Telaah" />
    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><Button variant="ghost" class="-ml-3 gap-2" as-child><Link href="/back-office/executive/inbox"><ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke Inbox Pimpinan</Link></Button><div class="mt-3 flex items-center gap-2"><Badge variant="outline" class="border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200">Koordinasi administratif</Badge><Badge v-if="previewMode" variant="outline">Mode pratinjau lokal</Badge></div><h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Koordinasi Telaah</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">Pantau status permintaan telaah Staf Ahli untuk membantu pengaturan administrasi dan tindak lanjut.</p></div>
            <div class="rounded-2xl border border-indigo-200/70 bg-indigo-50/50 p-4 text-xs leading-5 text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/25 dark:text-indigo-200"><div class="flex items-center gap-2 font-semibold"><Eye class="size-4" aria-hidden="true" /> Ringkasan status saja</div><p class="mt-1">Isi laporan dan berkas telaah tetap berada pada ruang Wali Kota.</p></div>
        </div>

        <Alert><Info class="size-4" aria-hidden="true" /><AlertTitle>Peran Sekda pada halaman ini</AlertTitle><AlertDescription>Anda dapat mengatur pemantauan waktu dan tindak lanjut administratif. Halaman ini tidak membuka isi pertimbangan Staf Ahli.</AlertDescription></Alert>

        <section v-if="consultations.length > 0" class="overflow-hidden rounded-3xl border bg-card shadow-xs"><div class="hidden grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_160px_180px] gap-4 border-b bg-muted/35 px-5 py-3 text-xs font-semibold text-muted-foreground md:grid"><span>Surat</span><span>Bidang telaah</span><span>Status</span><span>Waktu diminta</span></div><div v-for="consultation in consultations" :key="consultation.id" class="grid gap-3 border-b p-5 last:border-0 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_160px_180px] md:items-center md:gap-4"><div><p class="text-xs font-semibold text-muted-foreground">{{ consultation.letter.agenda_number }}</p><p class="mt-1 text-sm font-semibold">{{ consultation.letter.subject }}</p></div><div><p class="text-sm">{{ consultation.advisor.name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ consultation.advisor.holder_name || 'Pemegang belum ditetapkan' }}</p></div><div><ExpertConsultationStatusBadge :status="consultation.status" /></div><div class="flex items-center gap-2 text-xs text-muted-foreground"><CalendarClock class="size-4" aria-hidden="true" /> {{ consultation.requested_at }}</div></div></section>
        <section v-else class="grid min-h-64 place-items-center rounded-3xl border border-dashed bg-card/60 p-8 text-center"><div><ShieldCheck class="mx-auto size-10 text-muted-foreground" aria-hidden="true" /><h2 class="mt-3 font-semibold">Belum ada telaah untuk dipantau</h2><p class="mt-1 text-sm text-muted-foreground">Status koordinasi akan muncul setelah Wali Kota meminta telaah.</p></div></section>
    </main>
</template>

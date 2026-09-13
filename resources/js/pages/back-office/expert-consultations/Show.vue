<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, FileSearch, ShieldCheck, Upload, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import ExpertConsultationStatusBadge from '@/components/back-office/expert-consultations/ExpertConsultationStatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { previewExpertConsultations, previewExpertConsultationRoutes } from '@/lib/expertConsultationPreview';
import type { ExpertConsultationDetailProps, ReportExpertConsultationPayload } from '@/types';

const props = defineProps<ExpertConsultationDetailProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Tugas Telaah Staf Ahli', href: '/back-office/expert-consultations' },
            { title: 'Detail Tugas Telaah', href: '#' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const consultation = computed(() => {
    if (props.consultation) {
        return props.consultation;
    }

    if (previewMode.value) {
        return previewExpertConsultations[0] ?? null;
    }

    return null;
});
const canRespond = computed(() => previewMode.value || props.capabilities?.can_respond === true);
const summary = ref('');
const recommendation = ref('');
const document = ref<File | null>(null);
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const notice = ref('');

function onDocumentChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    document.value = input.files?.[0] ?? null;
}

function submit(): void {
    errors.value = {};
    notice.value = '';

    if (!summary.value.trim() || !recommendation.value.trim()) {
        errors.value = { report: 'Ringkasan dan rekomendasi wajib diisi.' };

        return;
    }

    const payload: ReportExpertConsultationPayload = {
        summary: summary.value.trim(),
        recommendation: recommendation.value.trim(),
        document: document.value,
    };

    if (previewMode.value) {
        processing.value = true;
        window.setTimeout(() => {
            notice.value = 'Hasil telaah tersimpan pada simulasi. Tidak ada data backend yang dibuat.';
            processing.value = false;
        }, 500);

        return;
    }

    if (!consultation.value?.links.report) {
        errors.value = { workflow: 'Endpoint laporan telaah belum tersedia dari server.' };

        return;
    }

    const form = new FormData();
    form.append('summary', payload.summary);
    form.append('recommendation', payload.recommendation);

    if (payload.document) {
        form.append('document', payload.document);
    }

    router.post(consultation.value.links.report, form, {
        forceFormData: true,
        preserveScroll: true,
        onStart: () => {
            processing.value = true;
        },
        onError: (responseErrors) => {
            errors.value = responseErrors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

const backUrl = computed(() =>
    previewMode.value ? previewExpertConsultationRoutes.index : (props.routes?.index ?? '/back-office/expert-consultations'),
);
</script>

<template>
    <Head :title="consultation ? `Telaah ${consultation.letter.agenda_number}` : 'Detail Tugas Telaah'" />

    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <template v-if="consultation">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div><Button variant="ghost" class="-ml-3 gap-2" as-child><a :href="backUrl"><ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke tugas telaah</a></Button><div class="mt-3 flex items-center gap-2"><ExpertConsultationStatusBadge :status="consultation.status" /><span class="text-xs text-muted-foreground">{{ consultation.letter.agenda_number }}</span></div><h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Detail tugas telaah</h1></div>
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/60 px-4 py-3 text-xs leading-5 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/25 dark:text-amber-200"><strong>{{ consultation.advisor.name }}</strong><br />{{ consultation.advisor.field }}</div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div class="space-y-6">
                    <section class="rounded-3xl border bg-card p-5 shadow-xs sm:p-6"><div class="flex items-start gap-3"><span class="flex size-10 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-700 dark:text-indigo-300"><FileSearch class="size-5" aria-hidden="true" /></span><div><h2 class="font-semibold">Surat yang perlu dipelajari</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">Baca informasi surat dan pahami bagian yang diminta Wali Kota.</p></div></div><dl class="mt-5 grid gap-4 border-t pt-5 sm:grid-cols-2"><div><dt class="text-xs text-muted-foreground">Nomor agenda</dt><dd class="mt-1 text-sm font-semibold">{{ consultation.letter.agenda_number }}</dd></div><div><dt class="text-xs text-muted-foreground">Tanggal diterima</dt><dd class="mt-1 text-sm font-semibold">{{ consultation.letter.received_at }}</dd></div><div class="sm:col-span-2"><dt class="text-xs text-muted-foreground">Perihal</dt><dd class="mt-1 text-sm font-semibold leading-6">{{ consultation.letter.subject }}</dd></div><div class="sm:col-span-2"><dt class="text-xs text-muted-foreground">Instansi pengirim</dt><dd class="mt-1 text-sm font-semibold">{{ consultation.letter.sender_organization_name }}</dd></div></dl><div class="mt-5 rounded-2xl border border-amber-200/70 bg-amber-50/50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"><p class="text-xs font-semibold text-amber-900 dark:text-amber-200">Pesan dari Wali Kota</p><p class="mt-1 text-sm leading-6 text-muted-foreground">{{ consultation.request_note || 'Tidak ada catatan tambahan. Berikan pertimbangan sesuai bidang keahlian Anda.' }}</p></div></section>

                    <section v-if="consultation.report" class="rounded-3xl border border-emerald-200/70 bg-emerald-50/35 p-5 shadow-xs dark:border-emerald-900/50 dark:bg-emerald-950/15 sm:p-6"><div class="flex items-start gap-3"><span class="flex size-10 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-300"><Check class="size-5" aria-hidden="true" /></span><div><h2 class="font-semibold">Hasil telaah sudah dikirim</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">{{ consultation.report.reported_at }} · {{ consultation.report.reported_by.name }}</p></div></div><div class="mt-5 space-y-4 border-t pt-5"><div><p class="text-xs font-semibold text-muted-foreground">Ringkasan</p><p class="mt-1 text-sm leading-6">{{ consultation.report.summary }}</p></div><div><p class="text-xs font-semibold text-muted-foreground">Rekomendasi</p><p class="mt-1 text-sm leading-6">{{ consultation.report.recommendation }}</p></div><div v-if="consultation.report.document" class="rounded-2xl border bg-background p-3 text-sm">Lampiran: <strong>{{ consultation.report.document.original_filename }}</strong></div></div></section>
                </div>

                <aside class="space-y-6">
                    <section v-if="canRespond && consultation.status === 'PENDING'" class="rounded-3xl border border-amber-200/80 bg-card p-5 shadow-xs dark:border-amber-900/50 sm:p-6"><div class="flex items-start gap-3"><span class="flex size-10 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-700 dark:text-amber-300"><UserRound class="size-5" aria-hidden="true" /></span><div><h2 class="font-semibold">Sampaikan hasil telaah</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">Tuliskan pertimbangan Anda. Setelah dikirim, laporan tidak dapat diubah.</p></div></div><div class="mt-5 space-y-4"><div><label for="expert-summary" class="text-xs font-semibold">Ringkasan telaah <span aria-hidden="true">*</span></label><Textarea id="expert-summary" v-model="summary" maxlength="4000" class="mt-1.5 min-h-28" placeholder="Apa hal utama yang Anda temukan?" :disabled="processing" /></div><div><label for="expert-recommendation" class="text-xs font-semibold">Rekomendasi untuk Wali Kota <span aria-hidden="true">*</span></label><Textarea id="expert-recommendation" v-model="recommendation" maxlength="4000" class="mt-1.5 min-h-32" placeholder="Apa pertimbangan atau langkah yang Anda sarankan?" :disabled="processing" /></div><div><label for="expert-document" class="text-xs font-semibold">Lampiran PDF <span class="font-normal text-muted-foreground">(opsional)</span></label><input id="expert-document" type="file" accept="application/pdf,.pdf" class="mt-1.5 block w-full rounded-xl border bg-background px-3 py-2 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-muted file:px-2 file:py-1 file:text-xs" :disabled="processing" @change="onDocumentChange" /><p class="mt-1 text-xs text-muted-foreground">PDF pendukung bersifat privat dan menjadi bagian dari riwayat telaah.</p></div><InputError :message="errors.report || errors.summary || errors.recommendation || errors.document || errors.workflow" /><Button type="button" class="min-h-11 w-full bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-400" :disabled="processing" @click="submit"><Upload v-if="!processing" class="size-4" aria-hidden="true" /><span>{{ processing ? 'Menyimpan hasil...' : 'Kirim hasil telaah' }}</span></Button></div></section>
                    <Alert v-if="notice" class="border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/50 dark:bg-emerald-950/20"><Check class="size-4 text-emerald-600" aria-hidden="true" /><AlertTitle>Berhasil</AlertTitle><AlertDescription>{{ notice }}</AlertDescription></Alert>
                    <Alert class="border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-900/50"><ShieldCheck class="size-4" aria-hidden="true" /><AlertTitle>Batas peran</AlertTitle><AlertDescription>Hasil telaah dikirim kembali kepada Wali Kota. Staf Ahli tidak meneruskan surat ke Asisten atau Kepala Bagian.</AlertDescription></Alert>
                </aside>
            </div>
        </template>
        <Alert v-else variant="destructive" class="rounded-2xl"><FileSearch class="size-4" aria-hidden="true" /><AlertTitle>Tugas telaah tidak ditemukan</AlertTitle><AlertDescription>Data tidak tersedia atau Anda tidak memiliki akses.</AlertDescription></Alert>
    </main>
</template>

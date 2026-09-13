<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpenCheck, Check, Clock3, FileSearch, ShieldCheck } from '@lucide/vue';
import { computed, reactive } from 'vue';
import ExpertConsultationStatusBadge from '@/components/back-office/expert-consultations/ExpertConsultationStatusBadge.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { previewExpertConsultations, previewExpertConsultationRoutes } from '@/lib/expertConsultationPreview';
import type { ExpertConsultation, ExpertConsultationFilters, ExpertConsultationPageProps } from '@/types';

const props = defineProps<ExpertConsultationPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Tugas Telaah Staf Ahli', href: '/back-office/expert-consultations' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const filters = reactive<ExpertConsultationFilters>({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const sourceConsultations = computed<ExpertConsultation[]>(() =>
    previewMode.value ? previewExpertConsultations : (props.consultations ?? []),
);
const filteredConsultations = computed(() => {
    const query = filters.search.trim().toLocaleLowerCase('id-ID');

    return sourceConsultations.value.filter((consultation) => {
        const matchesSearch = !query || [
            consultation.letter.agenda_number,
            consultation.letter.subject,
            consultation.letter.sender_organization_name,
            consultation.advisor.name,
        ].some((value) => value.toLocaleLowerCase('id-ID').includes(query));

        return matchesSearch && (!filters.status || consultation.status === filters.status);
    });
});
const indexUrl = computed(() =>
    previewMode.value ? previewExpertConsultationRoutes.index : (props.routes?.index ?? '/back-office/expert-consultations'),
);
const pendingCount = computed(() => sourceConsultations.value.filter((item) => item.status === 'PENDING').length);
const reportedCount = computed(() => sourceConsultations.value.filter((item) => item.status === 'REPORTED').length);

function showUrl(consultation: ExpertConsultation): string {
    return consultation.links.show || `${indexUrl.value}/${consultation.id}`;
}

function resetFilters(): void {
    filters.search = '';
    filters.status = '';
}

</script>

<template>
    <Head title="Tugas Telaah Staf Ahli" />

    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section class="relative overflow-hidden rounded-3xl border border-amber-200/80 bg-gradient-to-r from-amber-50 via-background to-orange-50/70 p-6 shadow-sm dark:border-amber-900/50 dark:from-amber-950/30 dark:via-background dark:to-orange-950/20 sm:p-8">
            <div class="pointer-events-none absolute -top-24 -right-16 size-56 rounded-full bg-amber-400/15 blur-3xl" aria-hidden="true" />
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" class="border-amber-300/80 bg-amber-100/70 text-amber-800 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-200">
                            <FileSearch class="mr-1.5 size-3.5" aria-hidden="true" />
                            Ruang telaah
                        </Badge>
                        <Badge v-if="previewMode" variant="outline" class="border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200">
                            Mode pratinjau lokal
                        </Badge>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Tugas Telaah Staf Ahli</h1>
                    <p class="text-sm leading-7 text-muted-foreground sm:text-base">
                        Baca surat yang ditugaskan Wali Kota, berikan pertimbangan sesuai bidang Anda, lalu kirimkan hasilnya kembali kepada Wali Kota.
                    </p>
                </div>
                <div class="rounded-2xl border border-amber-200/80 bg-background/80 p-4 text-sm shadow-xs dark:border-amber-900/50">
                    <div class="flex items-center gap-2 text-muted-foreground"><ShieldCheck class="size-4 text-amber-600 dark:text-amber-300" aria-hidden="true" /> Hasil hanya untuk Wali Kota</div>
                    <p class="mt-2 text-xs leading-5 text-muted-foreground">Staf Ahli tidak meneruskan surat ke Asisten atau Kepala Bagian.</p>
                </div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border bg-card p-4 shadow-xs"><div class="flex items-center gap-2 text-xs font-semibold text-muted-foreground"><Clock3 class="size-4 text-amber-600" aria-hidden="true" /> Menunggu tindakan</div><p class="mt-2 text-2xl font-bold">{{ pendingCount }}</p></div>
            <div class="rounded-2xl border bg-card p-4 shadow-xs"><div class="flex items-center gap-2 text-xs font-semibold text-muted-foreground"><Check class="size-4 text-emerald-600" aria-hidden="true" /> Sudah dilaporkan</div><p class="mt-2 text-2xl font-bold">{{ reportedCount }}</p></div>
            <div class="rounded-2xl border bg-card p-4 shadow-xs"><div class="flex items-center gap-2 text-xs font-semibold text-muted-foreground"><BookOpenCheck class="size-4 text-indigo-600" aria-hidden="true" /> Total tugas</div><p class="mt-2 text-2xl font-bold">{{ sourceConsultations.length }}</p></div>
        </div>

        <section class="rounded-2xl border bg-card p-4 shadow-xs sm:p-5" aria-label="Filter tugas telaah">
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end">
                <div class="space-y-1.5"><label for="expert-search" class="text-xs font-semibold">Cari surat atau bidang</label><Input id="expert-search" v-model="filters.search" placeholder="Nomor agenda, perihal, instansi, bidang..." /></div>
                <div class="space-y-1.5"><label class="text-xs font-semibold">Status</label><Select v-model="filters.status"><SelectTrigger><SelectValue placeholder="Semua status" /></SelectTrigger><SelectContent><SelectItem value="">Semua status</SelectItem><SelectItem value="PENDING">Menunggu telaah</SelectItem><SelectItem value="REPORTED">Sudah dilaporkan</SelectItem><SelectItem value="CANCELLED">Dibatalkan</SelectItem></SelectContent></Select></div>
                <button type="button" class="min-h-10 rounded-xl border px-4 text-sm font-semibold transition-colors hover:bg-muted" @click="resetFilters">Reset</button>
            </div>
        </section>

        <Alert v-if="!previewMode && !props.consultations" class="border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/20">
            <ShieldCheck class="size-4" aria-hidden="true" /><AlertTitle>Data tugas belum tersedia</AlertTitle><AlertDescription>Halaman ini menunggu endpoint telaah Staf Ahli dari backend. Tidak ada data contoh yang ditampilkan pada mode produksi.</AlertDescription>
        </Alert>

        <section v-if="filteredConsultations.length > 0" class="grid gap-4 lg:grid-cols-2">
            <Link v-for="consultation in filteredConsultations" :key="consultation.id" :href="showUrl(consultation)" class="group rounded-3xl border bg-card p-5 shadow-xs transition-all hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md dark:hover:border-amber-800 sm:p-6">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-semibold tracking-wide text-muted-foreground">{{ consultation.letter.agenda_number }}</p><h2 class="mt-1 line-clamp-2 text-base font-bold group-hover:text-amber-700 dark:group-hover:text-amber-300">{{ consultation.letter.subject }}</h2></div><ExpertConsultationStatusBadge :status="consultation.status" /></div>
                <div class="mt-4 grid gap-2 text-xs text-muted-foreground"><p><span class="font-semibold text-foreground">Bidang:</span> {{ consultation.advisor.field }}</p><p><span class="font-semibold text-foreground">Diminta oleh:</span> Wali Kota · {{ consultation.requested_at }}</p><p class="truncate"><span class="font-semibold text-foreground">Instansi:</span> {{ consultation.letter.sender_organization_name }}</p></div>
                <div class="mt-5 flex items-center justify-between border-t pt-4 text-xs font-semibold text-amber-700 dark:text-amber-300"><span>{{ consultation.status === 'PENDING' ? 'Buka dan sampaikan hasil telaah' : 'Lihat hasil telaah' }}</span><span aria-hidden="true">→</span></div>
            </Link>
        </section>

        <section v-else class="grid min-h-64 place-items-center rounded-3xl border border-dashed bg-card/60 p-8 text-center"><div><FileSearch class="mx-auto size-10 text-muted-foreground" aria-hidden="true" /><h2 class="mt-3 font-semibold">Tidak ada tugas yang cocok</h2><p class="mt-1 text-sm text-muted-foreground">Coba ubah kata pencarian atau pilih status lain.</p></div></section>
    </main>
</template>

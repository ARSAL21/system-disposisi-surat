<script setup lang="ts">
import {
    CheckCircle2,
    Clock,
    Download,
    FileSignature,
    FileText,
    Fingerprint,
} from '@lucide/vue';
import { ref } from 'vue';

const activeGuideStep = ref<number>(1);

interface GuideStep {
    id: number;
    stepTag: string;
    title: string;
    targetRole: string;
    shortDesc: string;
    highlights: string[];
}

const guideSteps: GuideStep[] = [
    {
        id: 1,
        stepTag: 'Tahap 01',
        title: 'Pengajuan Mandiri & Kunci SHA-256',
        targetRole: 'Warga / Instansi Pemohon',
        shortDesc:
            'Daftar akun pemohon online, unggah naskah permohonan format PDF, dan sistem otomatis mengunci integritas berkas dengan sidik jari digital SHA-256.',
        highlights: [
            'Verifikasi akun via email resmi',
            'Unggah PDF maksimal 20 MB',
            'Bukti tanda terima digital instan',
        ],
    },
    {
        id: 2,
        stepTag: 'Tahap 02',
        title: 'Verifikasi & Pengesahan Nomor Agenda',
        targetRole: 'Petugas Surat & Kabag Umum',
        shortDesc:
            'Petugas memeriksa kelengkapan administratif naskah. Kabag Umum mengesahkan dan menerbitkan nomor agenda resmi instansi.',
        highlights: [
            'Pemeriksaan checklist 4 butir administrasi',
            'Penerbitan nomor agenda unik',
            'Status berubah menjadi REGISTERED',
        ],
    },
    {
        id: 3,
        stepTag: 'Tahap 03',
        title: 'Disposisi Eksekutif & Telaah Paralel',
        targetRole: 'Sekda, Asisten & Kepala Bagian',
        shortDesc:
            'Pimpinan mengarahkan instruksi disposisi secara berjenjang ke Asisten dan dinas teknis (Hukum, Ekonomi, Aset) untuk ditindaklanjuti secara simultan.',
        highlights: [
            'Penegakan hierarki alur turun tanpa bypass',
            'Percabangan paralel multi-bagian independen',
            'Penyusunan bahan usulan dalam satu dossier',
        ],
    },
    {
        id: 4,
        stepTag: 'Tahap 04',
        title: 'Mandat Resmi & Unduh Dokumen Balasan',
        targetRole: 'Pimpinan & Pemohon Online',
        shortDesc:
            'Pimpinan menetapkan mandat penerbitan balasan. Surat keluar resmi bernomor diterbitkan dan langsung dapat diunduh oleh pemohon di portal.',
        highlights: [
            'Penerbitan surat balasan resmi ber-SHA256',
            'Notifikasi email & status balasan siap',
            'Akses unduh privat berkekuatan hukum',
        ],
    },
];
</script>

<template>
    <section
        id="panduan-online"
        class="mx-auto max-w-6xl px-3 py-14 sm:px-6 sm:py-24"
    >
        <!-- Section Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-end"
        >
            <div class="max-w-2xl space-y-2">
                <div
                    class="inline-flex items-center gap-1.5 text-xs font-bold tracking-wider text-indigo-600 uppercase dark:text-indigo-400"
                >
                    <FileSignature class="size-4" />
                    <span>Mekanisme Layanan Online</span>
                </div>
                <h2
                    class="font-['Syne',sans-serif] text-2xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                >
                    Alur Praktis Disposisi & Pengajuan Online
                </h2>
                <p
                    class="text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-300"
                >
                    Transparansi penuh dari saat surat Anda diunggah hingga
                    terbitnya naskah balasan resmi dari Pemerintah Kota.
                </p>
            </div>

            <!-- Quick Step Indicators -->
            <div
                class="flex items-center gap-1.5 rounded-2xl border border-slate-200/80 bg-white/70 p-1.5 shadow-2xs dark:border-slate-800 dark:bg-slate-900/60"
            >
                <button
                    v-for="step in guideSteps"
                    :key="step.id"
                    type="button"
                    class="flex size-8 items-center justify-center rounded-xl font-mono text-xs font-bold transition-all"
                    :class="[
                        activeGuideStep === step.id
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800',
                    ]"
                    @click="activeGuideStep = step.id"
                >
                    {{ step.id }}
                </button>
            </div>
        </div>

        <!-- 4 Step Interactive Showcase Grid -->
        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left Side: Interactive Step Cards (5 Cols) -->
            <div class="space-y-3 lg:col-span-5">
                <div
                    v-for="step in guideSteps"
                    :key="step.id"
                    class="cursor-pointer rounded-2xl border p-4.5 transition-all duration-200"
                    :class="[
                        activeGuideStep === step.id
                            ? 'border-indigo-600 bg-white shadow-lg shadow-indigo-500/10 dark:border-indigo-500 dark:bg-slate-900'
                            : 'border-slate-200/70 bg-white/50 hover:border-slate-300 dark:border-slate-800/80 dark:bg-slate-900/40 dark:hover:border-slate-700',
                    ]"
                    @click="activeGuideStep = step.id"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="font-mono text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                activeGuideStep === step.id
                                    ? 'text-indigo-600 dark:text-indigo-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ step.stepTag }} &bull; {{ step.targetRole }}
                        </span>
                        <div
                            class="size-2 rounded-full"
                            :class="
                                activeGuideStep === step.id
                                    ? 'animate-pulse bg-indigo-600 dark:bg-indigo-400'
                                    : 'bg-transparent'
                            "
                        />
                    </div>
                    <h3
                        class="mt-1 font-['Syne',sans-serif] text-sm font-bold text-slate-900 dark:text-white"
                    >
                        {{ step.title }}
                    </h3>
                    <p
                        class="mt-1 line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                    >
                        {{ step.shortDesc }}
                    </p>
                </div>
            </div>

            <!-- Right Side: Dynamic Visual Sandbox for the Selected Step (7 Cols) -->
            <div class="lg:col-span-7">
                <div
                    class="relative h-full min-h-[360px] rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-xl shadow-slate-900/5 backdrop-blur-xl sm:p-8 dark:border-slate-800 dark:bg-slate-900/90"
                >
                    <!-- Detail Header -->
                    <div
                        class="border-b border-slate-200/70 pb-5 dark:border-slate-800"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="font-mono text-xs font-bold text-indigo-600 uppercase dark:text-indigo-400"
                            >
                                Simulasi Tampilan Portal
                            </span>
                            <span
                                class="inline-flex items-center gap-1 font-mono text-[11px] text-muted-foreground"
                            >
                                <Clock class="size-3.5 text-slate-400" />
                                <span>Real-Time Audit</span>
                            </span>
                        </div>
                        <h3
                            class="mt-1.5 font-['Syne',sans-serif] text-xl font-extrabold text-slate-900 sm:text-2xl dark:text-white"
                        >
                            {{ guideSteps[activeGuideStep - 1].title }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-slate-600 dark:text-slate-300"
                        >
                            {{ guideSteps[activeGuideStep - 1].shortDesc }}
                        </p>
                    </div>

                    <!-- Step-Specific Interactive Micro-Mockups -->
                    <div class="mt-6">
                        <!-- Step 1 Mockup: Upload & Cryptographic Seal -->
                        <div v-if="activeGuideStep === 1" class="space-y-4">
                            <div
                                class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/70"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex size-8 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400"
                                        >
                                            <FileText class="size-4" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-900 dark:text-white"
                                            >
                                                permohonan-sentra-umkm.pdf
                                            </p>
                                            <p
                                                class="font-mono text-[10px] text-muted-foreground"
                                            >
                                                Ukuran: 2.4 MB &bull; Terkunci
                                                Otomatis
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border border-sky-500/30 bg-sky-500/10 px-2.5 py-0.5 font-mono text-[10px] font-bold text-sky-700 dark:text-sky-300"
                                    >
                                        SUBMITTED
                                    </span>
                                </div>

                                <div
                                    class="mt-3 flex items-center justify-between rounded-xl bg-white p-2.5 text-xs dark:bg-slate-900"
                                >
                                    <div
                                        class="flex items-center gap-2 font-mono text-[11px]"
                                    >
                                        <Fingerprint
                                            class="size-3.5 text-indigo-600 dark:text-indigo-400"
                                        />
                                        <span class="text-muted-foreground"
                                            >SHA-256:</span
                                        >
                                        <span
                                            class="font-bold text-slate-800 dark:text-slate-200"
                                            >e3b0c442...991b7852</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                        >INTEGRITY_OK</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 Mockup: Registry & Agenda Number -->
                        <div
                            v-else-if="activeGuideStep === 2"
                            class="space-y-4"
                        >
                            <div
                                class="rounded-2xl border border-amber-500/20 bg-amber-50/30 p-4 dark:border-amber-500/30 dark:bg-amber-950/20"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-500/20 pb-3"
                                >
                                    <div>
                                        <span
                                            class="font-mono text-[10px] font-bold text-amber-700 uppercase dark:text-amber-400"
                                            >Pengesahan Kabag Umum</span
                                        >
                                        <h4
                                            class="font-mono text-sm font-bold text-slate-900 dark:text-white"
                                        >
                                            Agenda: 0001/SETDA/IX/2026
                                        </h4>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 font-mono text-[10px] font-bold text-emerald-700 dark:text-emerald-300"
                                    >
                                        REGISTERED
                                    </span>
                                </div>
                                <div
                                    class="mt-3 grid grid-cols-2 gap-2 font-mono text-xs"
                                >
                                    <div
                                        class="rounded-xl bg-white p-2 text-slate-800 dark:bg-slate-900 dark:text-slate-200"
                                    >
                                        <span
                                            class="block text-[10px] text-muted-foreground"
                                            >Instansi Pengirim:</span
                                        >
                                        <span class="text-[11px] font-semibold"
                                            >Forum UMKM Baubau</span
                                        >
                                    </div>
                                    <div
                                        class="rounded-xl bg-white p-2 text-slate-800 dark:bg-slate-900 dark:text-slate-200"
                                    >
                                        <span
                                            class="block text-[10px] text-muted-foreground"
                                            >Routing Awal:</span
                                        >
                                        <span
                                            class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400"
                                            >Sekretaris Daerah</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 Mockup: Multi-Branch Internal Execution -->
                        <div
                            v-else-if="activeGuideStep === 3"
                            class="space-y-3"
                        >
                            <div
                                class="rounded-2xl border border-indigo-500/20 bg-indigo-50/30 p-4 dark:border-indigo-500/30 dark:bg-indigo-950/20"
                            >
                                <span
                                    class="font-mono text-[10px] font-bold text-indigo-700 uppercase dark:text-indigo-400"
                                    >Status Cabang Disposisi Berjalan:</span
                                >
                                <div class="mt-2 space-y-2 font-mono text-xs">
                                    <div
                                        class="flex items-center justify-between rounded-xl bg-white p-2.5 shadow-2xs dark:bg-slate-900"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="size-2 rounded-full bg-emerald-500"
                                            />
                                            <span
                                                class="font-semibold text-slate-900 dark:text-white"
                                                >Bagian Hukum</span
                                            >
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                            >Telaah Selesai (Bahan v2)</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between rounded-xl bg-white p-2.5 shadow-2xs dark:bg-slate-900"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="size-2 animate-pulse rounded-full bg-indigo-500"
                                            />
                                            <span
                                                class="font-semibold text-slate-900 dark:text-white"
                                                >Bagian Ekonomi</span
                                            >
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400"
                                            >Penyusunan Rekomendasi</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 Mockup: Official Response Available -->
                        <div
                            v-else-if="activeGuideStep === 4"
                            class="space-y-4"
                        >
                            <div
                                class="rounded-2xl border border-emerald-500/20 bg-emerald-50/30 p-4 dark:border-emerald-500/30 dark:bg-emerald-950/20"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-emerald-500/20 pb-3"
                                >
                                    <div>
                                        <span
                                            class="font-mono text-[10px] font-bold text-emerald-700 uppercase dark:text-emerald-400"
                                            >Surat Balasan Resmi Terbit</span
                                        >
                                        <h4
                                            class="font-mono text-xs font-bold text-slate-900 dark:text-white"
                                        >
                                            No: 0001/BALASAN/SETDA/IX/2026
                                        </h4>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2.5 py-0.5 font-mono text-[10px] font-bold text-emerald-700 dark:text-emerald-300"
                                    >
                                        DELIVERED
                                    </span>
                                </div>
                                <div
                                    class="mt-3 flex items-center justify-between"
                                >
                                    <p
                                        class="text-xs text-slate-600 dark:text-slate-300"
                                    >
                                        Tanggapan Resmi Wali Kota/Sekda atas
                                        Sentra UMKM
                                    </p>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-emerald-700"
                                    >
                                        <Download class="size-3.5" />
                                        <span>Unduh PDF</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step Highlights List -->
                    <div
                        class="mt-6 border-t border-slate-200/70 pt-4 dark:border-slate-800"
                    >
                        <span
                            class="font-mono text-[10px] font-bold text-muted-foreground uppercase"
                        >
                            Poin Kunci Tahap Ini:
                        </span>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div
                                v-for="point in guideSteps[activeGuideStep - 1]
                                    .highlights"
                                :key="point"
                                class="flex items-center gap-2 rounded-xl bg-slate-50 p-2 text-xs font-medium text-slate-800 dark:bg-slate-800/60 dark:text-slate-200"
                            >
                                <CheckCircle2
                                    class="size-3.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                                />
                                <span class="truncate">{{ point }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

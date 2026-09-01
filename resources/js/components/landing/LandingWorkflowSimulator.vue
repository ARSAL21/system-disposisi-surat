<script setup lang="ts">
import { Check, Cpu, RefreshCw } from '@lucide/vue';
import { ref } from 'vue';

const activeSimStage = ref<number>(1);
const isSimAutoPlaying = ref<boolean>(false);
let simInterval: ReturnType<typeof setInterval> | null = null;

const workflowStages = [
    {
        id: 1,
        code: 'GENERAL_AFFAIRS',
        title: '01. Registrasi & Verifikasi Dokumen',
        role: 'Staf Bagian Umum',
        badgeColor: 'bg-sky-500/10 text-sky-700 border-sky-500/20 dark:text-sky-300',
        description:
            'Surat masuk diterima, diperiksa kelengkapannya, dan diterbitkan stempel registrasi serta kalkulasi sidik jari digital SHA-256 untuk menjamin integritas naskah asli.',
        invariants: [
            'Verifikasi berkas fisik & digital',
            'Penerbitan nomor agenda unik',
            'Kalkulasi SHA-256 Hash Dokumen Asli',
        ],
        badge: 'Tahap Penerimaan',
    },
    {
        id: 2,
        code: 'EXECUTIVE_ENTRY',
        title: '02. Telaah & Disposisi Pimpinan',
        role: 'Wali Kota / Sekretaris Daerah',
        badgeColor: 'bg-amber-500/10 text-amber-700 border-amber-500/20 dark:text-amber-300',
        description:
            'Pimpinan eksekutif memeriksa naskah, menetapkan tingkat urgensi (Biasa, Penting, Rahasia, Sangat Segera), dan memberikan instruksi disposisi primer.',
        invariants: [
            'Penetapan instruksi kebijakan',
            'Penentuan tenggat batas waktu (SLA)',
            'Disposisi primer ke Asisten / Koordinator',
        ],
        badge: 'Tahap Strategis',
    },
    {
        id: 3,
        code: 'ASSISTANT',
        title: '03. Koordinasi & Percabangan (Branching)',
        role: 'Asisten Daerah / Kepala Bagian',
        badgeColor: 'bg-indigo-500/10 text-indigo-700 border-indigo-500/20 dark:text-indigo-300',
        description:
            'Asisten menjabarkan arahan pimpinan menjadi disposisi cabang taktis kepada satu atau beberapa Kepala Seksi / Subbagian pelaksana teknis.',
        invariants: [
            'Percabangan disposisi multi-penerima',
            'Sinkronisasi hierarki turunan',
            'Delegasi tugas operasional spesifik',
        ],
        badge: 'Tahap Taktis',
    },
    {
        id: 4,
        code: 'SECTION_HEAD',
        title: '04. Eksekusi & Penyelesaian Terminal',
        role: 'Kepala Seksi / Pelaksana Teknis',
        badgeColor: 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:text-emerald-300',
        description:
            'Tindak lanjut teknis dilaksanakan. Setelah seluruh cabang diselesaikan, status agregat surat secara otomatis berubah menjadi Selesai (Completed).',
        invariants: [
            'Laporan tindak lanjut & telaah staf',
            'Penutupan cabang disposisi aktif',
            'Agregasi status surat otomatis tuntas',
        ],
        badge: 'Tahap Eksekusi',
    },
];

function setSimStage(stageId: number) {
    activeSimStage.value = stageId;
}

function toggleSimAutoplay() {
    if (isSimAutoPlaying.value) {
        if (simInterval) {
clearInterval(simInterval);
}

        isSimAutoPlaying.value = false;
    } else {
        isSimAutoPlaying.value = true;
        simInterval = setInterval(() => {
            activeSimStage.value =
                activeSimStage.value >= 4 ? 1 : activeSimStage.value + 1;
        }, 3200);
    }
}
</script>

<template>
    <section id="workflow" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
        <div class="rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white/95 to-slate-50/90 p-6 shadow-2xl shadow-slate-900/5 backdrop-blur-2xl sm:p-10 dark:border-slate-800 dark:from-slate-900/90 dark:to-slate-950/95">
            <!-- Sandbox Title & Control Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200/70 pb-6 dark:border-slate-800">
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        <Cpu class="size-4" />
                        <span>Simulasi Interaktif Alur Surat Masuk</span>
                    </div>
                    <h2 class="mt-1 font-['Syne',sans-serif] text-2xl font-extrabold text-slate-900 sm:text-3xl dark:text-white">
                        Siklus Hidup Dokumen Kedinasan
                    </h2>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-300/80 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    @click="toggleSimAutoplay"
                >
                    <RefreshCw
                        class="size-3.5"
                        :class="{ 'animate-spin': isSimAutoPlaying }"
                    />
                    <span>{{ isSimAutoPlaying ? 'Hentikan Putar Otomatis' : 'Putar Simulasi Otomatis' }}</span>
                </button>
            </div>

            <!-- Stage Selector Pills -->
            <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <button
                    v-for="stage in workflowStages"
                    :key="stage.id"
                    type="button"
                    class="flex flex-col items-start gap-1 rounded-2xl border p-3.5 text-left transition-all duration-200"
                    :class="[
                        activeSimStage === stage.id
                            ? 'border-indigo-600 bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                            : 'border-slate-200/80 bg-white/60 text-slate-700 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300',
                    ]"
                    @click="setSimStage(stage.id)"
                >
                    <span
                        class="font-mono text-[10px] font-bold uppercase tracking-wider"
                        :class="activeSimStage === stage.id ? 'text-indigo-200' : 'text-muted-foreground'"
                    >
                        Stage 0{{ stage.id }}
                    </span>
                    <span class="text-xs font-bold truncate w-full">
                        {{ stage.code }}
                    </span>
                </button>
            </div>

            <!-- Active Stage Deep Dive Display -->
            <div class="mt-8 rounded-2xl border border-slate-200/70 bg-white/70 p-6 sm:p-8 dark:border-slate-800 dark:bg-slate-950/60">
                <div
                    v-for="stage in workflowStages"
                    v-show="activeSimStage === stage.id"
                    :key="stage.id"
                    class="space-y-6"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="space-y-1">
                            <span
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                :class="stage.badgeColor"
                            >
                                {{ stage.badge }} · {{ stage.code }}
                            </span>
                            <h3 class="font-['Syne',sans-serif] text-xl font-bold text-slate-900 sm:text-2xl dark:text-white">
                                {{ stage.title }}
                            </h3>
                            <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                Aparatur Penanggung Jawab: {{ stage.role }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-300">
                        {{ stage.description }}
                    </p>

                    <!-- Invariants List -->
                    <div class="rounded-xl border border-slate-200/60 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                        <span class="font-mono text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                            System Invariants & Validasi Server-Side:
                        </span>
                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            <div
                                v-for="inv in stage.invariants"
                                :key="inv"
                                class="flex items-center gap-2 rounded-lg bg-white p-2 text-xs font-medium text-slate-800 shadow-xs dark:bg-slate-800 dark:text-slate-200"
                            >
                                <Check class="size-3.5 shrink-0 text-emerald-500" />
                                <span class="truncate">{{ inv }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

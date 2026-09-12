<script setup lang="ts">
import {
    Check,
    CheckCircle2,
    Copy,
    FileSearch,
    Fingerprint,
    Search,
} from '@lucide/vue';
import { ref } from 'vue';

const trackingQuery = ref<string>('0001/SETDA/IX/2026');

interface TrackingRecord {
    number: string;
    sender: string;
    subject: string;
    receivedAt: string;
    status: string;
    statusBadge: string;
    hash: string;
    currentAssignee: string;
    dispositionSteps: {
        title: string;
        actor: string;
        time: string;
        done: boolean;
    }[];
}

const sampleQueries = [
    { label: 'Sentra UMKM Baubau', code: '0001/SETDA/IX/2026' },
    { label: 'Fasilitasi Adat Baubau', code: '0002/SETDA/IX/2026' },
];

const trackingResult = ref<TrackingRecord>({
    number: '0001/SETDA/IX/2026',
    sender: 'Forum UMKM Kota Baubau',
    subject: 'Permohonan Koordinasi Pengembangan Sentra UMKM Kota Baubau',
    receivedAt: '28 Agustus 2026 &bull; 09:15 WITA',
    status: 'Balasan Resmi Terbit',
    statusBadge:
        'bg-emerald-500/10 text-emerald-700 border-emerald-500/30 dark:text-emerald-300',
    hash: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    currentAssignee: 'Pemohon Online (Dokumen Siap Diunduh)',
    dispositionSteps: [
        {
            title: 'Pengajuan Mandiri & Kunci SHA-256',
            actor: 'Pemohon Online',
            time: '28 Agt 09:15',
            done: true,
        },
        {
            title: 'Pemeriksaan & Pengesahan Agenda',
            actor: 'Petugas Surat & Kabag Umum',
            time: '28 Agt 10:00',
            done: true,
        },
        {
            title: 'Disposisi Sekda & Telaah Paralel',
            actor: 'Sekda, Asisten II, Bagian Hukum & Ekonomi',
            time: '28 Agt 14:20',
            done: true,
        },
        {
            title: 'Mandat Selesai: 0001/BALASAN/SETDA/IX/2026',
            actor: 'Sekda & Petugas Surat Keluar',
            time: '29 Agt 11:30',
            done: true,
        },
    ],
});

function applySample(code: string) {
    trackingQuery.value = code;
}

const isCopied = ref<boolean>(false);
function copyHash() {
    navigator.clipboard.writeText(trackingResult.value.hash);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
}
</script>

<template>
    <section
        id="tracking"
        class="mx-auto max-w-6xl px-3 py-14 sm:px-6 sm:py-24"
    >
        <!-- Section Header (Centered, Clean) -->
        <div class="mx-auto max-w-2xl space-y-2 text-center">
            <div
                class="inline-flex items-center gap-1.5 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-400/30 dark:text-indigo-300"
            >
                <FileSearch class="size-3.5" />
                <span>Pelacakan Mandiri Publik</span>
            </div>

            <h2
                class="font-['Syne',sans-serif] text-2xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
            >
                Pantau Status Dokumen Secara Terbuka
            </h2>

            <p
                class="text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-300"
            >
                Gunakan nomor agenda resmi untuk memantau perjalanan naskah dan
                mengunduh balasan secara real-time.
            </p>
        </div>

        <!-- Tracking Console Card (Mobile Responsive) -->
        <div
            class="mx-auto mt-8 max-w-3xl rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-xl shadow-slate-950/5 backdrop-blur-2xl sm:p-7 dark:border-slate-800 dark:bg-slate-900/90"
        >
            <!-- Search Console Input -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="trackingQuery"
                        type="text"
                        placeholder="Ketik nomor agenda (contoh: 0001/SETDA/IX/2026)..."
                        class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50/80 pr-4 pl-10 font-mono text-xs text-foreground focus:border-indigo-600 focus:outline-none dark:border-slate-800 dark:bg-slate-950/80"
                    />
                </div>

                <button
                    type="button"
                    class="flex h-12 items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 text-xs font-bold text-white shadow-md shadow-indigo-600/25 transition-all hover:bg-indigo-700 active:scale-[0.98]"
                >
                    <Search class="size-4" />
                    <span>Lacak Dokumen</span>
                </button>
            </div>

            <!-- Quick-Pick Demo Chips for Mobile Users -->
            <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
                <span class="font-mono text-[10px] text-muted-foreground"
                    >Contoh Cepat:</span
                >
                <button
                    v-for="sample in sampleQueries"
                    :key="sample.code"
                    type="button"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1 font-mono text-[10px] text-slate-700 transition-all hover:border-indigo-500 hover:bg-indigo-50/50 hover:text-indigo-700 active:scale-[0.97] dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300"
                    @click="applySample(sample.code)"
                >
                    {{ sample.label }} ({{ sample.code }})
                </button>
            </div>

            <!-- Result Card Panel -->
            <div
                class="mt-6 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-950/70"
            >
                <!-- Result Header -->
                <div
                    class="flex flex-col gap-2 border-b border-slate-200/70 pb-3.5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800"
                >
                    <div>
                        <span
                            class="font-mono text-[10px] font-bold text-muted-foreground uppercase"
                        >
                            NOMOR AGENDA TERKONFIRMASI
                        </span>
                        <p
                            class="font-mono text-xs font-extrabold text-slate-900 sm:text-sm dark:text-white"
                        >
                            {{ trackingResult.number }}
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit items-center gap-1 rounded-full border px-3 py-1 font-mono text-[10px] font-bold"
                        :class="trackingResult.statusBadge"
                    >
                        <CheckCircle2 class="size-3 text-emerald-500" />
                        <span>{{ trackingResult.status }}</span>
                    </span>
                </div>

                <!-- Perihal & Info -->
                <div class="mt-3.5 space-y-1.5 text-xs">
                    <h4
                        class="leading-snug font-bold text-slate-900 dark:text-white"
                    >
                        {{ trackingResult.subject }}
                    </h4>
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-muted-foreground"
                    >
                        <span
                            >Pemohon:
                            <strong class="text-foreground">{{
                                trackingResult.sender
                            }}</strong></span
                        >
                        <span
                            >Waktu:
                            <strong class="text-foreground">{{
                                trackingResult.receivedAt
                            }}</strong></span
                        >
                    </div>
                </div>

                <!-- Vertical Disposition Stepper -->
                <div
                    class="mt-5 border-t border-slate-200/70 pt-4 dark:border-slate-800"
                >
                    <span
                        class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Tahapan Penanganan Berkas:
                    </span>

                    <div class="mt-2.5 space-y-2">
                        <div
                            v-for="(
                                step, idx
                            ) in trackingResult.dispositionSteps"
                            :key="idx"
                            class="flex items-start gap-2.5 rounded-xl border border-slate-200/60 bg-white p-2.5 sm:items-center sm:justify-between dark:border-slate-800/80 dark:bg-slate-900"
                        >
                            <div
                                class="flex items-start gap-2.5 sm:items-center"
                            >
                                <div
                                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-[10px] font-bold text-white sm:mt-0"
                                >
                                    <Check class="size-3" />
                                </div>
                                <div class="text-xs">
                                    <p
                                        class="font-semibold text-slate-900 dark:text-white"
                                    >
                                        {{ step.title }}
                                    </p>
                                    <p
                                        class="font-mono text-[10px] text-muted-foreground"
                                    >
                                        Aktor: {{ step.actor }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="shrink-0 pt-0.5 font-mono text-[10px] text-muted-foreground sm:pt-0"
                            >
                                {{ step.time }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cryptographic Fingerprint Box -->
                <div
                    class="mt-4 flex flex-col gap-2 rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-3 text-xs sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <Fingerprint
                            class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                        />
                        <div class="truncate">
                            <span
                                class="font-mono text-[10px] text-muted-foreground"
                                >SHA-256:
                            </span>
                            <span
                                class="truncate font-mono text-[10px] font-bold text-indigo-700 dark:text-indigo-300"
                            >
                                {{ trackingResult.hash.substring(0, 24) }}...
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="flex items-center justify-center gap-1 rounded-lg border border-indigo-500/30 bg-white/80 px-3 py-1 font-mono text-[10px] font-bold text-indigo-700 hover:bg-white active:scale-[0.97] dark:bg-slate-800 dark:text-indigo-300"
                        @click="copyHash"
                    >
                        <Check
                            v-if="isCopied"
                            class="size-3 text-emerald-500"
                        />
                        <Copy v-else class="size-3" />
                        <span>{{
                            isCopied ? 'Tersalin' : 'Salin Sidik Jari'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

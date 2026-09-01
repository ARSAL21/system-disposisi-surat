<script setup lang="ts">
import {
    Check,
    Clock,
    Copy,
    QrCode,
    Search,
} from '@lucide/vue';
import { ref } from 'vue';

const trackingQuery = ref<string>('SRT/2026/08/0492');
const trackingResult = ref<{
    found: boolean;
    number: string;
    sender: string;
    subject: string;
    receivedAt: string;
    status: string;
    statusBadge: string;
    hash: string;
    currentAssignee: string;
    dispositionSteps: { title: string; time: string; done: boolean }[];
}>({
    found: true,
    number: 'SRT/2026/08/0492',
    sender: 'Kementerian Pendayagunaan Aparatur Negara & RB',
    subject: 'Permohonan Validasi Kebijakan Transformasi Digital Layanan Terpadu',
    receivedAt: '28 Agustus 2026, 09:14 WITA',
    status: 'Sedang Didisposisikan',
    statusBadge: 'bg-indigo-500/10 text-indigo-700 border-indigo-500/30 dark:text-indigo-300',
    hash: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    currentAssignee: 'Bagian Organisasi & Tata Laksana',
    dispositionSteps: [
        { title: 'Registrasi & Verifikasi Berkas Asli (SHA-256 Valid)', time: '28 Agt 09:15', done: true },
        { title: 'Disposisi Primer: Wali Kota -> Sekda', time: '28 Agt 11:30', done: true },
        { title: 'Koordinasi Taktis: Asisten Administrasi Umum', time: '29 Agt 08:45', done: true },
        { title: 'Tindak Lanjut Teknis: Bagian Organisasi & Tata Laksana', time: 'Sedang Proses', done: false },
    ],
});

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
    <section id="tracking" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <!-- Left Context Column (5 Cols) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                    <Search class="size-4" />
                    <span>Layanan Publik Terbuka</span>
                </div>
                <h2 class="font-['Syne',sans-serif] text-2xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                    Pelacakan Berkas Publik Transparan
                </h2>
                <p class="text-xs leading-relaxed text-muted-foreground sm:text-sm">
                    Masyarakat dan pemohon surat dapat memantau setiap langkah disposisi secara langsung tanpa perlu datang ke kantor dinas.
                </p>

                <div class="pt-2 space-y-2">
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-3.5 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400">
                            <QrCode class="size-5" />
                        </div>
                        <div class="text-xs">
                            <p class="font-bold text-slate-900 dark:text-white">Nomor Agenda & Barcode Unik</p>
                            <p class="text-muted-foreground">Otomatis terbit saat surat diverifikasi.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-3.5 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:bg-teal-400/10 dark:text-teal-400">
                            <Clock class="size-5" />
                        </div>
                        <div class="text-xs">
                            <p class="font-bold text-slate-900 dark:text-white">Riwayat Waktu Real-Time</p>
                            <p class="text-muted-foreground">Catatan timestamp akurat per tahapan disposisi.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Interactive Tracking Panel (7 Cols) -->
            <div class="lg:col-span-7">
                <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-xl shadow-slate-900/5 backdrop-blur-2xl dark:border-slate-800 dark:bg-slate-900/90">
                    <!-- Tracking Input Search -->
                    <div class="relative flex items-center gap-2">
                        <div class="relative flex-1">
                            <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                v-model="trackingQuery"
                                type="text"
                                placeholder="Masukkan nomor agenda surat (contoh: SRT/2026/08/0492)..."
                                class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/70 pl-10 pr-4 text-xs font-mono text-foreground focus:border-indigo-600 focus:outline-none dark:border-slate-800 dark:bg-slate-950/70"
                            />
                        </div>
                        <button
                            type="button"
                            class="h-11 rounded-2xl bg-indigo-600 px-5 text-xs font-bold text-white shadow-md hover:bg-indigo-700"
                        >
                            Cek Status
                        </button>
                    </div>

                    <!-- Live Simulated Result Card -->
                    <div class="mt-6 rounded-2xl border border-slate-200/70 bg-slate-50/60 p-5 dark:border-slate-800 dark:bg-slate-950/60">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/60 pb-3 dark:border-slate-800">
                            <div>
                                <span class="font-mono text-[10px] font-bold text-muted-foreground">NOMOR SURAT RESMI</span>
                                <p class="font-mono text-xs font-bold text-slate-900 dark:text-white">
                                    {{ trackingResult.number }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 font-mono text-[10px] font-bold"
                                :class="trackingResult.statusBadge"
                            >
                                {{ trackingResult.status }}
                            </span>
                        </div>

                        <div class="mt-3 space-y-2 text-xs">
                            <div>
                                <span class="text-muted-foreground">Perihal Surat: </span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ trackingResult.subject }}</span>
                            </div>
                            <div class="flex flex-wrap gap-4 text-muted-foreground">
                                <span>Instansi: <strong class="text-foreground">{{ trackingResult.sender }}</strong></span>
                                <span>Diterima: <strong class="text-foreground">{{ trackingResult.receivedAt }}</strong></span>
                            </div>
                        </div>

                        <!-- Stepper Timeline -->
                        <div class="mt-5 space-y-2 border-t border-slate-200/60 pt-4 dark:border-slate-800">
                            <span class="font-mono text-[10px] font-bold text-muted-foreground uppercase">
                                Perjalanan Disposisi Berkas:
                            </span>
                            <div class="space-y-2 pt-1">
                                <div
                                    v-for="(step, idx) in trackingResult.dispositionSteps"
                                    :key="idx"
                                    class="flex items-center justify-between gap-2 rounded-xl bg-white p-2.5 text-xs shadow-2xs dark:bg-slate-900"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex size-5 items-center justify-center rounded-full text-white text-[10px] font-bold"
                                            :class="step.done ? 'bg-emerald-500' : 'bg-indigo-600 animate-pulse'"
                                        >
                                            <Check v-if="step.done" class="size-3" />
                                            <span v-else>{{ idx + 1 }}</span>
                                        </div>
                                        <span :class="step.done ? 'text-slate-800 dark:text-slate-200' : 'font-bold text-indigo-600 dark:text-indigo-400'">
                                            {{ step.title }}
                                        </span>
                                    </div>
                                    <span class="font-mono text-[10px] text-muted-foreground shrink-0">
                                        {{ step.time }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Cryptographic Fingerprint Box -->
                        <div class="mt-4 flex items-center justify-between gap-2 rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-2.5 text-xs">
                            <div class="truncate">
                                <span class="text-[10px] font-mono text-muted-foreground">SHA-256 Fingerprint: </span>
                                <span class="font-mono text-[10px] text-indigo-600 dark:text-indigo-400 truncate">
                                    {{ trackingResult.hash.substring(0, 32) }}...
                                </span>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 font-mono text-[10px] font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                                @click="copyHash"
                            >
                                <Check v-if="isCopied" class="size-3 text-emerald-500" />
                                <Copy v-else class="size-3" />
                                <span>{{ isCopied ? 'Tersalin' : 'Salin Hash' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

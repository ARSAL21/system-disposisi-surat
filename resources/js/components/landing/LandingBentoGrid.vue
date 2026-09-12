<script setup lang="ts">
import {
    Check,
    Copy,
    FileLock2,
    Fingerprint,
    GitBranch,
    GitCommit,
    Layers,
    Lock,
    ShieldCheck,
} from '@lucide/vue';
import { ref } from 'vue';

const sampleHash =
    'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
const isCopied = ref<boolean>(false);

function copyHash() {
    navigator.clipboard.writeText(sampleHash);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
}
</script>

<template>
    <section
        id="security"
        class="mx-auto max-w-6xl px-3 py-14 sm:px-6 sm:py-24"
    >
        <!-- Section Header -->
        <div class="max-w-2xl space-y-2">
            <div
                class="inline-flex items-center gap-1.5 text-xs font-bold tracking-wider text-indigo-600 uppercase dark:text-indigo-400"
            >
                <ShieldCheck class="size-4" />
                <span>Arsitektur Keamanan & Standar Kedinasan</span>
            </div>
            <h2
                class="font-['Syne',sans-serif] text-2xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
            >
                Fondasi Rekayasa Sistem Tanpa Kompromi
            </h2>
            <p
                class="text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-300"
            >
                Dirancang untuk memenuhi kepatuhan tata naskah dinas pemerintah
                dengan integritas kriptografis dan pemisahan kewenangan mutlak.
            </p>
        </div>

        <!-- Gapless Bento Grid (2+1 and 1+2 = 6 units, mathematically verified) -->
        <div
            class="mt-10 grid grid-flow-dense grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3"
        >
            <!-- CARD 1: 2-Col Span (SHA-256 Cryptographic Integrity) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-indigo-500/50 hover:shadow-xl sm:p-8 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div
                    class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start"
                >
                    <div class="space-y-2">
                        <div
                            class="flex size-11 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400"
                        >
                            <Fingerprint class="size-6" />
                        </div>
                        <h3
                            class="font-['Syne',sans-serif] text-xl font-bold text-slate-900 dark:text-white"
                        >
                            Integritas Kriptografi SHA-256 Dokumen Asli
                        </h3>
                        <p
                            class="max-w-md text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-300"
                        >
                            Setiap berkas surat dikalkulasi sidik jari digital
                            (SHA-256) saat registrasi. Sistem menolak manipulasi
                            dokumen secara mutlak.
                        </p>
                    </div>

                    <div
                        class="w-full max-w-xs rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 font-mono text-xs dark:border-slate-800 dark:bg-slate-950/70"
                    >
                        <div
                            class="flex items-center justify-between text-[11px] font-bold text-muted-foreground"
                        >
                            <span>SIDIK JARI DIGITAL</span>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-[10px] text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                                @click="copyHash"
                            >
                                <Check
                                    v-if="isCopied"
                                    class="size-3 text-emerald-500"
                                />
                                <Copy v-else class="size-3" />
                                <span>{{
                                    isCopied ? 'Tersalin' : 'Salin'
                                }}</span>
                            </button>
                        </div>
                        <p
                            class="mt-2 font-mono text-[10px] break-all text-indigo-600 dark:text-indigo-400"
                        >
                            {{ sampleHash }}
                        </p>
                        <div
                            class="mt-3 flex items-center justify-between border-t border-slate-200/60 pt-2 text-[10px] dark:border-slate-800"
                        >
                            <span class="text-muted-foreground"
                                >STATUS DOKUMEN</span
                            >
                            <span
                                class="font-bold text-emerald-600 dark:text-emerald-400"
                                >TERKUNCI & VALID</span
                            >
                        </div>
                    </div>
                </div>
            </article>

            <!-- CARD 2: 1-Col Span (Strict Downward Flow) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-amber-500/50 hover:shadow-xl sm:p-8 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div
                    class="flex size-11 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400"
                >
                    <Layers class="size-6" />
                </div>
                <h3
                    class="mt-4 font-['Syne',sans-serif] text-lg font-bold text-slate-900 dark:text-white"
                >
                    Strict Downward Flow
                </h3>
                <p
                    class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-300"
                >
                    Disposisi bergerak satu arah: Level 10 (Pimpinan) &rarr; 20
                    (Asisten) &rarr; 30 (Kabag). Lompatan atau bypass horizontal
                    diblokir di level database.
                </p>
                <div
                    class="mt-4 flex items-center gap-2 font-mono text-[11px] font-bold text-amber-600 dark:text-amber-400"
                >
                    <Lock class="size-3.5" />
                    <span>ENFORCED AT DATABASE LEVEL</span>
                </div>
            </article>

            <!-- CARD 3: 1-Col Span (Append-Only Audit Trail) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-emerald-500/50 hover:shadow-xl sm:p-8 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div
                    class="flex size-11 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400"
                >
                    <GitCommit class="size-6" />
                </div>
                <h3
                    class="mt-4 font-['Syne',sans-serif] text-lg font-bold text-slate-900 dark:text-white"
                >
                    Append-Only Audit Log
                </h3>
                <p
                    class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-300"
                >
                    Setiap telaah, delegasi, perubahan dokumen, dan terbitnya
                    surat keluar dicatat abadi tanpa endpoint edit atau hapus.
                </p>
                <div
                    class="mt-4 flex items-center gap-2 font-mono text-[11px] font-bold text-emerald-600 dark:text-emerald-400"
                >
                    <FileLock2 class="size-3.5" />
                    <span>JEJAK FORENSIK 100% IMMUTABLE</span>
                </div>
            </article>

            <!-- CARD 4: 2-Col Span (Branching & Parallel Coordination) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-indigo-500/50 hover:shadow-xl sm:p-8 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div
                    class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start"
                >
                    <div class="space-y-2">
                        <div
                            class="flex size-11 items-center justify-center rounded-2xl bg-teal-500/10 text-teal-600 dark:bg-teal-400/10 dark:text-teal-400"
                        >
                            <GitBranch class="size-6" />
                        </div>
                        <h3
                            class="font-['Syne',sans-serif] text-xl font-bold text-slate-900 dark:text-white"
                        >
                            Percabangan Multi-Dinas & Mandat Balasan
                        </h3>
                        <p
                            class="max-w-md text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-300"
                        >
                            Satu permohonan dapat ditindaklanjuti secara paralel
                            ke beberapa dinas teknis. Status surat cerdas
                            mengagregasi seluruh cabang sampai mandat balasan
                            resmi terpenuhi.
                        </p>
                    </div>

                    <div
                        class="flex w-full max-w-xs flex-col gap-2 font-mono text-xs"
                    >
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-950/70"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="size-2 rounded-full bg-emerald-500"
                                />
                                <span>Bagian Hukum</span>
                            </div>
                            <span
                                class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                >TELAAH SELESAI</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-950/70"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="size-2 rounded-full bg-emerald-500"
                                />
                                <span>Bagian Pembangunan</span>
                            </div>
                            <span
                                class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                >TELAAH SELESAI</span
                            >
                        </div>
                        <div
                            class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 p-2 text-center text-[11px] font-bold text-indigo-700 dark:text-indigo-300"
                        >
                            MANDAT RESMI: DISETUJUI SEKDA
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

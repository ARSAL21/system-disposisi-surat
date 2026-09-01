<script setup lang="ts">
import {
    FileLock2,
    Fingerprint,
    GitBranch,
    GitCommit,
    Layers,
    Lock,
    ShieldCheck,
} from '@lucide/vue';
</script>

<template>
    <section id="security" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
        <!-- Section Header -->
        <div class="max-w-2xl space-y-2">
            <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                <ShieldCheck class="size-4" />
                <span>Arsitektur Keamanan & Standar Kedinasan</span>
            </div>
            <h2 class="font-['Syne',sans-serif] text-2xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                Fondasi Rekayasa Sistem Tanpa Kompromi
            </h2>
            <p class="text-xs leading-relaxed text-muted-foreground sm:text-sm">
                Dirancang untuk memenuhi kepatuhan tata naskah dinas pemerintah dengan integritas kriptografis dan pemisahan kewenangan mutlak.
            </p>
        </div>

        <!-- Bento Grid (Mathematically Verified 2+1 and 1+2 = 6 units) -->
        <div class="mt-10 grid grid-flow-dense grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
            <!-- CARD 1: 2-Col Span (SHA-256 Cryptographic Integrity) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-indigo-500/50 hover:shadow-xl sm:p-8 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">
                    <div class="space-y-2">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400">
                            <Fingerprint class="size-6" />
                        </div>
                        <h3 class="font-['Syne',sans-serif] text-xl font-bold text-slate-900 dark:text-white">
                            Verifikasi Integritas SHA-256 Dokumen Asli
                        </h3>
                        <p class="max-w-md text-xs leading-relaxed text-muted-foreground sm:text-sm">
                            Setiap berkas surat yang diunggah dikalkulasi sidik jari digital (SHA-256) saat registrasi. Sistem menjamin berkas tidak mengalami manipulasi selama proses disposisi berlangsung.
                        </p>
                    </div>

                    <div class="w-full max-w-xs rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4 font-mono text-xs dark:border-slate-800 dark:bg-slate-950/70">
                        <div class="flex items-center justify-between text-[11px] font-bold text-muted-foreground">
                            <span>HASH KALKULASI ASLI</span>
                            <ShieldCheck class="size-3.5 text-emerald-500" />
                        </div>
                        <p class="mt-2 text-[10px] text-indigo-600 break-all dark:text-indigo-400">
                            e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
                        </p>
                        <div class="mt-3 flex items-center justify-between border-t border-slate-200/60 pt-2 text-[10px] text-muted-foreground dark:border-slate-800">
                            <span>STATUS: IMMUTABLE</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">TERVALIDASI</span>
                        </div>
                    </div>
                </div>
            </article>

            <!-- CARD 2: 1-Col Span (Strict Downward Flow) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-amber-500/50 hover:shadow-xl sm:p-8 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div class="flex size-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                    <Layers class="size-6" />
                </div>
                <h3 class="mt-4 font-['Syne',sans-serif] text-lg font-bold text-slate-900 dark:text-white">
                    Strict Downward Flow
                </h3>
                <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                    Disposisi hanya dapat diteruskan ke hierarki yang lebih rendah (Level 10 -> 20 -> 30 -> 40). Bypass horizontal maupun lompatan vertikal diblokir di level database.
                </p>
                <div class="mt-4 flex items-center gap-2 font-mono text-[11px] font-bold text-amber-600 dark:text-amber-400">
                    <Lock class="size-3.5" />
                    <span>ENFORCED BY WORKFLOW SPEC</span>
                </div>
            </article>

            <!-- CARD 3: 1-Col Span (Append-Only Audit Trail) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-emerald-500/50 hover:shadow-xl sm:p-8 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div class="flex size-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                    <GitCommit class="size-6" />
                </div>
                <h3 class="mt-4 font-['Syne',sans-serif] text-lg font-bold text-slate-900 dark:text-white">
                    Append-Only Audit Log
                </h3>
                <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                    Setiap pergantian penerima, pengubahan naskah, dan penetapan disposisi dicatat permanen dalam audit log tanpa endpoint penghapusan.
                </p>
                <div class="mt-4 flex items-center gap-2 font-mono text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                    <FileLock2 class="size-3.5" />
                    <span>100% FORENSIK RECORD</span>
                </div>
            </article>

            <!-- CARD 4: 2-Col Span (Branching & Multi-Recipient Parallel Execution) -->
            <article
                class="group relative col-span-1 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-black/5 transition-all duration-300 hover:border-indigo-500/50 hover:shadow-xl sm:p-8 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">
                    <div class="space-y-2">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-teal-500/10 text-teal-600 dark:bg-teal-400/10 dark:text-teal-400">
                            <GitBranch class="size-6" />
                        </div>
                        <h3 class="font-['Syne',sans-serif] text-xl font-bold text-slate-900 dark:text-white">
                            Percabangan Disposisi & Agregasi Status Otomatis
                        </h3>
                        <p class="max-w-md text-xs leading-relaxed text-muted-foreground sm:text-sm">
                            Satu surat dapat didisposisikan secara paralel ke berbagai dinas/seksi berbeda. Status surat secara cerdas mengagregasi seluruh cabang dan hanya selesai ketika seluruh penerima menuntaskan tindak lanjut.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 font-mono text-xs">
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-950/70">
                            <div class="size-2 rounded-full bg-emerald-500" />
                            <span>Cabang A: Selesai</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-950/70">
                            <div class="size-2 rounded-full bg-indigo-500 animate-pulse" />
                            <span>Cabang B: Sedang Proses</span>
                        </div>
                        <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 p-2 text-center text-[10px] font-bold text-indigo-700 dark:text-indigo-300">
                            AGREGAT: DALAM_DISPOSISI
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    FileCheck2,
    FileInput,
    GitFork,
    Search,
} from '@lucide/vue';
import gsap from 'gsap';
import { nextTick, onMounted, ref } from 'vue';
import { register } from '@/routes';

const heroHeadlineRef = ref<HTMLElement | null>(null);
const heroSubtextRef = ref<HTMLElement | null>(null);
const heroCtaRef = ref<HTMLElement | null>(null);
const heroCardRef = ref<HTMLElement | null>(null);

onMounted(() => {
    nextTick(() => {
        const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

        if (heroHeadlineRef.value) {
            tl.fromTo(
                heroHeadlineRef.value,
                { opacity: 0, y: 35 },
                { opacity: 1, y: 0, duration: 0.9 },
            );
        }

        if (heroSubtextRef.value) {
            tl.fromTo(
                heroSubtextRef.value,
                { opacity: 0, y: 25 },
                { opacity: 1, y: 0, duration: 0.8 },
                '-=0.6',
            );
        }

        if (heroCtaRef.value) {
            tl.fromTo(
                heroCtaRef.value,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.7 },
                '-=0.5',
            );
        }

        if (heroCardRef.value) {
            tl.fromTo(
                heroCardRef.value,
                { opacity: 0, y: 40, scale: 0.96 },
                { opacity: 1, y: 0, scale: 1, duration: 1 },
                '-=0.5',
            );
        }
    });
});
</script>

<template>
    <section class="relative mx-auto max-w-6xl px-4 pt-16 pb-20 sm:px-6 sm:pt-24 sm:pb-28">
        <!-- Hero Center Container -->
        <div class="mx-auto flex flex-col items-center text-center">
            <!-- Ultra-Wide Commanding H1 (Strict 2-3 Lines with Syne Typography, NO Live Badge on Top) -->
            <h1
                ref="heroHeadlineRef"
                class="max-w-5xl font-['Syne',sans-serif] text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl lg:text-[4.25rem] lg:leading-[1.08] dark:text-white"
            >
                Transformasi Tata Naskah Dinas & Alur Disposisi Presisi Tinggi
            </h1>

            <!-- Clean Subtitle -->
            <p
                ref="heroSubtextRef"
                class="mt-6 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base sm:leading-relaxed dark:text-slate-300"
            >
                Sistem manajemen persuratan resmi berbasis integritas kriptografi SHA-256,
                penegakan alur berjenjang 4 tingkat tanpa bypass, dan transparansi pelacakan berkas secara real-time.
            </p>

            <!-- High-Contrast Dual Action CTAs -->
            <div
                ref="heroCtaRef"
                class="mt-8 flex flex-wrap items-center justify-center gap-3 sm:gap-4"
            >
                <Link
                    :href="register()"
                    class="group inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition-all duration-200 hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-indigo-600/40"
                >
                    <span>Ajukan Surat Baru</span>
                    <ArrowRight class="size-4 transition-transform group-hover:translate-x-1" />
                </Link>

                <a
                    href="#tracking"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-300/80 bg-white/90 px-6 py-3.5 text-sm font-bold text-slate-800 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:hover:bg-slate-800"
                >
                    <Search class="size-4 text-indigo-600 dark:text-indigo-400" />
                    <span>Lacak Status Surat</span>
                </a>
            </div>
        </div>

        <!-- Hero Interactive Visual Showcase: Live Multi-Tier Disposition Card -->
        <div
            ref="heroCardRef"
            class="relative mx-auto mt-14 max-w-5xl rounded-3xl border border-slate-200/80 bg-white/80 p-4 shadow-2xl shadow-indigo-500/10 backdrop-blur-2xl sm:p-7 dark:border-slate-800/90 dark:bg-slate-900/85"
        >
            <!-- Card Top Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/70 pb-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold">
                        <FileCheck2 class="size-5" />
                    </div>
                    <div>
                        <h3 class="font-['Syne',sans-serif] text-sm font-bold text-slate-900 dark:text-white">
                            Simulasi Penegakan Alur Disposisi
                        </h3>
                        <p class="font-mono text-[10px] text-muted-foreground">
                            INVARIANT: STRICT_DOWNWARD_FLOW · SHA256_INTEGRITY_VERIFIED
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 font-mono text-[11px] font-bold text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-300">
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span>Alur Resmi Aktif</span>
                    </span>
                </div>
            </div>

            <!-- 4-Stage Horizontal Pipeline Preview -->
            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Stage 1 -->
                <div class="relative rounded-2xl border border-sky-500/30 bg-sky-50/40 p-4 dark:bg-sky-950/20">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-bold text-sky-700 dark:text-sky-300">01. INTAKE</span>
                        <FileInput class="size-4 text-sky-600 dark:text-sky-400" />
                    </div>
                    <h4 class="mt-2 text-xs font-bold text-slate-900 dark:text-white">
                        Bagian Umum
                    </h4>
                    <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-400">
                        Registrasi & Ekstraksi Hash Dokumen Asli SHA-256.
                    </p>
                </div>

                <!-- Stage 2 -->
                <div class="relative rounded-2xl border border-amber-500/30 bg-amber-50/40 p-4 dark:bg-amber-950/20">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-bold text-amber-700 dark:text-amber-300">02. EKSEKUTIF</span>
                        <Building2 class="size-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <h4 class="mt-2 text-xs font-bold text-slate-900 dark:text-white">
                        Wali Kota / Sekda
                    </h4>
                    <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-400">
                        Telaah kebijakan & disposisi primer terarah.
                    </p>
                </div>

                <!-- Stage 3 -->
                <div class="relative rounded-2xl border border-indigo-500/30 bg-indigo-50/40 p-4 dark:bg-indigo-950/20">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-bold text-indigo-700 dark:text-indigo-300">03. KOORDINASI</span>
                        <GitFork class="size-4 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <h4 class="mt-2 text-xs font-bold text-slate-900 dark:text-white">
                        Asisten & Kabag
                    </h4>
                    <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-400">
                        Percabangan disposisi multi-bidang taktis.
                    </p>
                </div>

                <!-- Stage 4 -->
                <div class="relative rounded-2xl border border-emerald-500/30 bg-emerald-50/40 p-4 dark:bg-emerald-950/20">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-300">04. TERMINAL</span>
                        <CheckCircle2 class="size-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <h4 class="mt-2 text-xs font-bold text-slate-900 dark:text-white">
                        Seksi & Pelaksana
                    </h4>
                    <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-400">
                        Eksekusi operasional & penutupan status tuntas.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

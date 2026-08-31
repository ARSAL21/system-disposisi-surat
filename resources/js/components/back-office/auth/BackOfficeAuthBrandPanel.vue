<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    CheckCircle2,
    Fingerprint,
    GitFork,
    Layers,
    Lock,
    ShieldCheck,
} from '@lucide/vue';
import gsap from 'gsap';
import { onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const appName = usePage().props.name;
const panelRef = ref<HTMLElement | null>(null);

onMounted(() => {
    if (!panelRef.value) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const ctx = gsap.context(() => {
        gsap.from('.brand-hero-badge', {
            opacity: 0,
            y: -16,
            duration: 0.7,
            ease: 'power3.out',
        });

        gsap.from('.brand-hero-title', {
            opacity: 0,
            y: 24,
            duration: 0.85,
            delay: 0.1,
            ease: 'power3.out',
        });

        gsap.from('.brand-hero-desc', {
            opacity: 0,
            y: 20,
            duration: 0.8,
            delay: 0.2,
            ease: 'power3.out',
        });

        gsap.from('.bento-item', {
            opacity: 0,
            y: 30,
            duration: 0.75,
            stagger: 0.12,
            delay: 0.3,
            ease: 'power3.out',
        });

        gsap.from('.brand-footer-text', {
            opacity: 0,
            duration: 1,
            delay: 0.7,
            ease: 'power2.out',
        });
    }, panelRef.value);

    return () => ctx.revert();
});
</script>

<template>
    <aside
        ref="panelRef"
        class="relative hidden min-h-svh flex-col justify-between overflow-hidden bg-slate-950 px-10 py-10 text-white lg:flex xl:px-14 xl:py-12"
    >
        <!-- Atmospheric Mesh & Dynamic Ambient Blurs -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -top-40 -left-40 size-[32rem] rounded-full bg-gradient-to-br from-indigo-600/30 via-blue-600/20 to-transparent blur-[100px]"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute top-1/2 -right-32 size-[36rem] -translate-y-1/2 rounded-full bg-gradient-to-bl from-violet-600/25 via-indigo-600/20 to-transparent blur-[120px]"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -bottom-32 left-1/4 size-[28rem] rounded-full bg-gradient-to-tr from-cyan-500/20 via-blue-700/15 to-transparent blur-[110px]"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(99,102,241,0.15),transparent_50%),radial-gradient(ellipse_at_bottom_right,rgba(56,189,248,0.1),transparent_50%)]"
        />

        <!-- Subtle Grid Pattern Overlay -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 [background-image:linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] [background-size:40px_40px] opacity-[0.03]"
        />

        <!-- Header / Identity Split Pill -->
        <header class="relative z-10 flex items-center justify-between">
            <Link
                :href="home()"
                class="group flex items-center gap-3 rounded-2xl p-1.5 transition-colors focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:outline-none"
            >
                <span
                    class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg ring-1 shadow-indigo-500/25 ring-white/20 transition-transform duration-300 group-hover:scale-105"
                >
                    <AppLogoIcon class="size-6 text-white" />
                </span>
                <div>
                    <span
                        class="block text-sm font-semibold tracking-tight text-white"
                    >
                        {{ appName }}
                    </span>
                    <span class="block text-xs font-medium text-indigo-300/80">
                        Portal Internal Aparatur
                    </span>
                </div>
            </Link>

            <div
                class="flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300 backdrop-blur-md"
            >
                <span class="relative flex size-2">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                    />
                    <span
                        class="relative inline-flex size-2 rounded-full bg-emerald-500"
                    />
                </span>
                Sistem Operasional
            </div>
        </header>

        <!-- Main Content Area: Wide Editorial Hero & Gapless Bento -->
        <div class="relative z-10 my-auto max-w-xl py-8">
            <!-- Hero Attention Badge -->
            <div
                class="brand-hero-badge mb-5 inline-flex items-center gap-2 rounded-full border border-indigo-400/25 bg-indigo-500/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-indigo-200 backdrop-blur-md"
            >
                <ShieldCheck class="size-4 text-indigo-400" />
                <span>Kewenangan Berjenjang & Ruang Kerja Terproteksi</span>
            </div>

            <!-- 2-Line Iron Rule Headline -->
            <h1
                class="brand-hero-title max-w-lg text-3xl font-extrabold tracking-tight text-white sm:text-4xl xl:text-5xl xl:leading-[1.15]"
            >
                Tata Kelola & Disposisi Surat Berintegritas Tinggi.
            </h1>

            <p
                class="brand-hero-desc mt-4 max-w-lg text-sm leading-relaxed text-slate-300 sm:text-base sm:leading-7"
            >
                Portal internal mengisolasi alur kerja aparatur dengan penugasan
                jabatan konkret, verifikasi SHA-256 dokumen asli, dan jejak
                audit yang tidak dapat dimanipulasi.
            </p>

            <!-- Gapless Bento Grid (Zero voids, dense flow) -->
            <div
                class="mt-8 grid grid-flow-dense grid-cols-2 gap-3.5"
                aria-label="Fitur keamanan dan tata kelola"
            >
                <!-- Bento 1: Hierarki Disposisi (col-span-1) -->
                <div
                    class="bento-item group relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.08] to-white/[0.02] p-4.5 backdrop-blur-lg transition-all duration-500 hover:border-indigo-400/40 hover:bg-white/[0.12]"
                >
                    <div
                        class="mb-3 flex size-9 items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-300 ring-1 ring-indigo-400/30 transition-transform duration-300 group-hover:scale-110"
                    >
                        <GitFork class="size-4.5" />
                    </div>
                    <h2 class="text-sm font-semibold text-white">
                        Alur 4 Tingkat
                    </h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-400">
                        Disposisi berjenjang dari Kepala hingga Staf Pelaksana
                        secara akurat.
                    </p>
                    <div
                        class="mt-3 flex items-center gap-1.5 text-[11px] font-medium text-indigo-300"
                    >
                        <CheckCircle2
                            class="size-3.5 shrink-0 text-emerald-400"
                        />
                        <span>Validasi Server-Side</span>
                    </div>
                </div>

                <!-- Bento 2: Kriptografi SHA-256 (col-span-1) -->
                <div
                    class="bento-item group relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.08] to-white/[0.02] p-4.5 backdrop-blur-lg transition-all duration-500 hover:border-cyan-400/40 hover:bg-white/[0.12]"
                >
                    <div
                        class="mb-3 flex size-9 items-center justify-center rounded-xl bg-cyan-500/20 text-cyan-300 ring-1 ring-cyan-400/30 transition-transform duration-300 group-hover:scale-110"
                    >
                        <Fingerprint class="size-4.5" />
                    </div>
                    <h2 class="text-sm font-semibold text-white">
                        Sidik Jari SHA-256
                    </h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-400">
                        Integritas berkas teruji anti-tamper dengan hash dokumen
                        unik.
                    </p>
                    <div
                        class="mt-3 flex items-center gap-1.5 text-[11px] font-medium text-cyan-300"
                    >
                        <CheckCircle2
                            class="size-3.5 shrink-0 text-emerald-400"
                        />
                        <span>Imutabel & Terverifikasi</span>
                    </div>
                </div>

                <!-- Bento 3: Audit Trail & MFA (col-span-2) -->
                <div
                    class="bento-item group relative col-span-2 overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.08] to-white/[0.02] p-4.5 backdrop-blur-lg transition-all duration-500 hover:border-violet-400/40 hover:bg-white/[0.12]"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex size-7 items-center justify-center rounded-lg bg-violet-500/20 text-violet-300 ring-1 ring-violet-400/30"
                                >
                                    <Layers class="size-3.5" />
                                </span>
                                <h2 class="text-sm font-semibold text-white">
                                    Audit Trail & Keamanan Step-up
                                </h2>
                            </div>
                            <p
                                class="mt-1.5 text-xs leading-relaxed text-slate-300"
                            >
                                Setiap mutasi peran, struktur, dan persetujuan
                                naskah dicatat secara *append-only* dengan
                                proteksi MFA dan passkey biometrik.
                            </p>
                        </div>
                        <div
                            class="hidden shrink-0 items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-2.5 py-1.5 text-[11px] font-medium text-slate-300 sm:flex"
                        >
                            <Activity
                                class="size-3.5 animate-pulse text-emerald-400"
                            />
                            <span>Live Monitor</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer / Safety Standard Note -->
        <footer
            class="brand-footer-text relative z-10 flex items-center justify-between border-t border-white/10 pt-5 text-xs text-slate-400"
        >
            <span class="flex items-center gap-2">
                <Lock class="size-3.5 text-indigo-400" />
                Terkoneksi jalur aman HTTPS
            </span>
            <span class="text-slate-500">
                Akses internal khusus aparatur berwenang
            </span>
        </footer>
    </aside>
</template>

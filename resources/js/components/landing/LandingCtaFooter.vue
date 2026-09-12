<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle,
    FileSignature,
    Lock,
    Shield,
    ShieldCheck,
} from '@lucide/vue';
import { login, register } from '@/routes';
</script>

<template>
    <footer class="mx-auto max-w-6xl px-3 pt-6 pb-12 sm:px-6 sm:pt-10 sm:pb-16">
        <!-- Civic Action Banner (Mobile-Friendly, High-Contrast) -->
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-slate-900 to-indigo-950 p-6 text-white shadow-2xl sm:p-10 lg:p-12"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-indigo-500/20 blur-3xl"
            />

            <div
                class="relative z-10 mx-auto max-w-2xl space-y-3 text-center sm:space-y-4"
            >
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-400/30 bg-indigo-500/10 px-3 py-1 font-mono text-[10px] font-bold text-indigo-300 uppercase"
                >
                    <ShieldCheck class="size-3.5 text-indigo-400" />
                    <span>Layanan Resmi Pemerintah Kota</span>
                </span>

                <h2
                    class="font-['Syne',sans-serif] text-2xl leading-tight font-extrabold tracking-tight sm:text-4xl lg:text-5xl"
                >
                    Mulai Ajukan Surat & Pantau Disposisi Anda
                </h2>

                <p
                    class="text-xs leading-relaxed text-indigo-200/90 sm:text-sm"
                >
                    Akses sistem tata naskah dinas digital untuk pengajuan
                    persuratan mandiri maupun disposisi aparatur secara cepat
                    dan transparan.
                </p>

                <!-- Dual Action Buttons (Mobile-first vertical stack -> desktop horizontal) -->
                <div
                    class="flex flex-col items-stretch justify-center gap-2.5 pt-3 sm:flex-row sm:items-center"
                >
                    <Link
                        :href="register()"
                        class="flex h-12 items-center justify-center gap-2 rounded-2xl bg-white px-6 text-xs font-bold text-slate-950 shadow-xl transition-all hover:bg-slate-100 active:scale-[0.98] sm:text-sm"
                    >
                        <span>Daftar Akun Pemohon</span>
                        <ArrowRight class="size-4" />
                    </Link>

                    <a
                        href="/back-office/login"
                        class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-6 text-xs font-bold text-white backdrop-blur-sm transition-all hover:bg-white/20 active:scale-[0.98] sm:text-sm"
                    >
                        <Lock class="size-4 text-indigo-300" />
                        <span>Masuk Portal Kedinasan</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Municipal Multi-Column Footer (Collapses into clean mobile stack) -->
        <div
            class="mt-12 grid grid-cols-1 gap-8 border-b border-slate-200/70 pb-10 sm:mt-16 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-800"
        >
            <!-- Col 1: Identity -->
            <div class="space-y-3">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-xs"
                    >
                        <FileSignature class="size-5" />
                    </div>
                    <div>
                        <span
                            class="block font-['Syne',sans-serif] text-base leading-tight font-extrabold text-slate-900 dark:text-white"
                        >
                            E-Disposisi
                        </span>
                        <span
                            class="font-mono text-[9px] text-muted-foreground uppercase"
                        >
                            Tata Naskah Dinas Terpadu
                        </span>
                    </div>
                </div>

                <p class="text-xs leading-relaxed text-muted-foreground">
                    Sistem resmi pengelolaan surat masuk, disposisi berjenjang,
                    dan penerbitan surat balasan berbasis enkripsi SHA-256.
                </p>

                <div
                    class="flex items-center gap-2 pt-1 font-mono text-[11px] text-emerald-600 dark:text-emerald-400"
                >
                    <div
                        class="size-2 animate-pulse rounded-full bg-emerald-500"
                    />
                    <span>Layanan Beroperasi Normal</span>
                </div>
            </div>

            <!-- Col 2: Layanan Publik -->
            <div class="space-y-2.5">
                <h4
                    class="font-['Syne',sans-serif] text-xs font-bold tracking-wider text-slate-900 uppercase dark:text-white"
                >
                    Layanan Pemohon
                </h4>
                <ul class="space-y-2 text-xs text-muted-foreground">
                    <li>
                        <Link
                            :href="register()"
                            class="transition-colors hover:text-foreground"
                        >
                            Registrasi Akun Baru
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="login()"
                            class="transition-colors hover:text-foreground"
                        >
                            Masuk Portal Pemohon
                        </Link>
                    </li>
                    <li>
                        <a
                            href="#tracking"
                            class="transition-colors hover:text-foreground"
                        >
                            Pelacakan Nomor Agenda
                        </a>
                    </li>
                    <li>
                        <a
                            href="#panduan-online"
                            class="transition-colors hover:text-foreground"
                        >
                            Panduan Surat Digital
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Col 3: Portal Internal -->
            <div class="space-y-2.5">
                <h4
                    class="font-['Syne',sans-serif] text-xs font-bold tracking-wider text-slate-900 uppercase dark:text-white"
                >
                    Aparatur Sipil
                </h4>
                <ul class="space-y-2 text-xs text-muted-foreground">
                    <li>
                        <a
                            href="/back-office/login"
                            class="transition-colors hover:text-foreground"
                        >
                            Login Petugas Surat
                        </a>
                    </li>
                    <li>
                        <a
                            href="/back-office/login"
                            class="transition-colors hover:text-foreground"
                        >
                            Inbox Disposisi Pimpinan
                        </a>
                    </li>
                    <li>
                        <a
                            href="/back-office/login"
                            class="transition-colors hover:text-foreground"
                        >
                            Penyusunan Dossier Balasan
                        </a>
                    </li>
                    <li>
                        <a
                            href="/back-office/login"
                            class="transition-colors hover:text-foreground"
                        >
                            Buku Register Surat Masuk
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Keamanan & Kepatuhan -->
            <div class="space-y-2.5">
                <h4
                    class="font-['Syne',sans-serif] text-xs font-bold tracking-wider text-slate-900 uppercase dark:text-white"
                >
                    Standar Kepatuhan
                </h4>
                <div class="space-y-2 text-xs text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <Shield
                            class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Kriptografi Dokumen SHA-256</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle
                            class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                        />
                        <span>Tata Kelola Permenpan RB</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Lock
                            class="size-4 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <span>Hak Akses Berbasis Jabatan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Copyright Bar -->
        <div
            class="mt-6 flex flex-col items-center justify-between gap-3 text-center sm:flex-row sm:text-left"
        >
            <p class="font-mono text-[11px] text-muted-foreground">
                &copy; 2026 Pemerintah Kota. Hak Cipta Dilindungi Undang-Undang.
            </p>
            <div class="flex items-center gap-4 text-xs text-muted-foreground">
                <span class="font-mono text-[10px]">Versi Sistem 2.6-MVP</span>
            </div>
        </div>
    </footer>
</template>

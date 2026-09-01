<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, FileSignature, Lock } from '@lucide/vue';
import { register } from '@/routes';
</script>

<template>
    <section class="mx-auto max-w-6xl px-4 pt-10 pb-20 sm:px-6">
        <!-- Massive Action Box -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-900 via-indigo-950 to-slate-950 p-8 text-white shadow-2xl sm:p-12">
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -right-20 -top-20 size-80 rounded-full bg-indigo-500/20 blur-3xl"
            />

            <div class="relative z-10 mx-auto max-w-3xl text-center space-y-4">
                <h2 class="font-['Syne',sans-serif] text-3xl font-extrabold tracking-tight sm:text-5xl">
                    Mulai Ajukan Surat & Pantau Dokumen Anda Sekarang
                </h2>
                <p class="text-xs leading-relaxed text-indigo-200/90 sm:text-sm">
                    Akses sistem tata naskah dinas terpadu untuk pengajuan persuratan masyarakat maupun disposisi aparatur sipil secara instan.
                </p>

                <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                    <Link
                        :href="register()"
                        class="inline-flex items-center gap-2 rounded-2xl bg-white px-7 py-3.5 text-xs font-bold text-slate-950 shadow-xl transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-100 sm:text-sm"
                    >
                        <span>Daftar Akun Pengaju</span>
                        <ArrowRight class="size-4" />
                    </Link>

                    <a
                        href="/back-office/login"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-7 py-3.5 text-xs font-bold text-white shadow-sm backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/20 sm:text-sm"
                    >
                        <Lock class="size-4" />
                        <span>Login Pegawai Kedinasan</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Municipal Footer -->
        <footer class="mt-16 border-t border-slate-200/70 pt-8 dark:border-slate-800">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <FileSignature class="size-4 text-indigo-600 dark:text-indigo-400" />
                    <span>Sistem E-Disposisi & Tata Naskah Dinas Terpadu</span>
                </div>

                <p class="text-center font-mono text-[11px] text-muted-foreground">
                    &copy; 2026 Pemerintah Kota. Seluruh hak cipta dilindungi undang-undang.
                </p>
            </div>
        </footer>
    </section>
</template>

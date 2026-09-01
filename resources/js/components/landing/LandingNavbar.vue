<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileSignature, Lock } from '@lucide/vue';
import { login } from '@/routes';
</script>

<template>
    <header class="sticky top-4 z-50 mx-auto w-full max-w-6xl px-4 sm:px-6">
        <nav
            class="flex items-center justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white/80 p-2.5 px-4 shadow-xl shadow-slate-900/5 backdrop-blur-2xl transition-all sm:px-6 dark:border-slate-800/80 dark:bg-slate-900/80 dark:shadow-black/20"
        >
            <!-- Brand Identity -->
            <Link href="/" class="group flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-teal-400 text-white shadow-md shadow-indigo-500/25 transition-transform duration-300 group-hover:scale-105"
                >
                    <FileSignature class="size-5" />
                </div>
                <div class="flex flex-col">
                    <span class="font-['Syne',sans-serif] text-base font-extrabold tracking-tight text-slate-900 dark:text-white">
                        E-Disposisi
                    </span>
                    <span class="font-mono text-[9px] font-semibold tracking-wider text-muted-foreground uppercase">
                        Tata Naskah Dinas
                    </span>
                </div>
            </Link>

            <!-- Nav Center Links -->
            <div class="hidden items-center gap-1 md:flex">
                <a
                    href="#workflow"
                    class="rounded-xl px-3.5 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-slate-100 hover:text-foreground dark:hover:bg-slate-800/60"
                >
                    Alur Disposisi
                </a>
                <a
                    href="#security"
                    class="rounded-xl px-3.5 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-slate-100 hover:text-foreground dark:hover:bg-slate-800/60"
                >
                    Integritas SHA-256
                </a>
                <a
                    href="#tracking"
                    class="rounded-xl px-3.5 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-slate-100 hover:text-foreground dark:hover:bg-slate-800/60"
                >
                    Pelacakan Surat
                </a>
            </div>

            <!-- Action Links -->
            <div class="flex items-center gap-2">
                <Link
                    :href="login()"
                    class="rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    Masuk Publik
                </Link>

                <a
                    href="/back-office/login"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-600/30 bg-indigo-600/10 px-3.5 py-2 text-xs font-bold text-indigo-700 transition-all hover:bg-indigo-600 hover:text-white dark:border-indigo-400/30 dark:bg-indigo-400/10 dark:text-indigo-300 dark:hover:bg-indigo-600 dark:hover:text-white"
                >
                    <Lock class="size-3" />
                    <span>Portal Kedinasan</span>
                </a>
            </div>
        </nav>
    </header>
</template>

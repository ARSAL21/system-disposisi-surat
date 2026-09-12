<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    FileSignature,
    Lock,
    Menu,
    Search,
    Shield,
    Workflow,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import { login, register } from '@/routes';

const isMobileMenuOpen = ref<boolean>(false);

function toggleMobileMenu() {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

function closeMobileMenu() {
    isMobileMenuOpen.value = false;
}
</script>

<template>
    <header class="sticky top-3 z-50 mx-auto w-full max-w-6xl px-3 sm:px-6">
        <nav
            class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white/85 p-2.5 px-3.5 shadow-xl shadow-slate-950/5 backdrop-blur-2xl transition-all sm:rounded-3xl sm:px-6 dark:border-slate-800/80 dark:bg-slate-900/85 dark:shadow-black/25"
        >
            <!-- Brand Monogram & Title -->
            <Link
                href="/"
                class="group flex items-center gap-2.5 sm:gap-3"
                @click="closeMobileMenu"
            >
                <div
                    class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-teal-400 text-white shadow-md shadow-indigo-500/25 transition-transform duration-300 group-hover:scale-105 sm:size-10 sm:rounded-2xl"
                >
                    <FileSignature class="size-4 sm:size-5" />
                </div>
                <div class="flex flex-col">
                    <span
                        class="font-['Syne',sans-serif] text-sm leading-tight font-extrabold tracking-tight text-slate-900 sm:text-base dark:text-white"
                    >
                        E-Disposisi
                    </span>
                    <span
                        class="font-mono text-[9px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Pemerintah Kota
                    </span>
                </div>
            </Link>

            <!-- Desktop Nav Links -->
            <div class="hidden items-center gap-1 md:flex">
                <a
                    href="#panduan-online"
                    class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                >
                    Alur Online
                </a>
                <a
                    href="#security"
                    class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                >
                    Integritas SHA-256
                </a>
                <a
                    href="#tracking"
                    class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                >
                    Lacak Berkas
                </a>
            </div>

            <!-- Desktop Action Buttons -->
            <div class="hidden items-center gap-2 md:flex">
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

            <!-- Mobile Hamburger Button -->
            <button
                type="button"
                aria-label="Menu Navigasi"
                class="flex size-10 items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 text-slate-700 transition-colors hover:bg-slate-100 md:hidden dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:bg-slate-700"
                @click="toggleMobileMenu"
            >
                <X v-if="isMobileMenuOpen" class="size-5" />
                <Menu v-else class="size-5" />
            </button>
        </nav>

        <!-- Mobile Drawer Overlay -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="isMobileMenuOpen"
                class="mt-2 flex flex-col gap-2 rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-2xl backdrop-blur-2xl md:hidden dark:border-slate-800 dark:bg-slate-900/95"
            >
                <!-- Quick Mobile Nav Links -->
                <div
                    class="flex flex-col gap-1 border-b border-slate-200/70 pb-3 dark:border-slate-800"
                >
                    <a
                        href="#panduan-online"
                        class="flex items-center justify-between rounded-xl p-3 text-xs font-semibold text-slate-800 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="closeMobileMenu"
                    >
                        <span class="flex items-center gap-2">
                            <Workflow
                                class="size-4 text-indigo-600 dark:text-indigo-400"
                            />
                            <span>Alur Pengajuan Online</span>
                        </span>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </a>

                    <a
                        href="#security"
                        class="flex items-center justify-between rounded-xl p-3 text-xs font-semibold text-slate-800 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="closeMobileMenu"
                    >
                        <span class="flex items-center gap-2">
                            <Shield
                                class="size-4 text-emerald-600 dark:text-emerald-400"
                            />
                            <span>Integritas Kriptografis SHA-256</span>
                        </span>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </a>

                    <a
                        href="#tracking"
                        class="flex items-center justify-between rounded-xl p-3 text-xs font-semibold text-slate-800 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="closeMobileMenu"
                    >
                        <span class="flex items-center gap-2">
                            <Search
                                class="size-4 text-sky-600 dark:text-sky-400"
                            />
                            <span>Pelacakan Berkas Publik</span>
                        </span>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </a>
                </div>

                <!-- Action CTA Stack for Mobile (Touch targets >= 44px) -->
                <div class="grid grid-cols-1 gap-2 pt-1">
                    <Link
                        :href="register()"
                        class="flex h-11 items-center justify-center rounded-2xl bg-indigo-600 px-4 text-xs font-bold text-white shadow-md shadow-indigo-600/20 active:scale-[0.98]"
                        @click="closeMobileMenu"
                    >
                        Ajukan Surat Baru
                    </Link>

                    <div class="grid grid-cols-2 gap-2">
                        <Link
                            :href="login()"
                            class="flex h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 hover:bg-slate-50 active:scale-[0.98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            @click="closeMobileMenu"
                        >
                            Masuk Pemohon
                        </Link>

                        <a
                            href="/back-office/login"
                            class="flex h-11 items-center justify-center gap-1.5 rounded-2xl border border-indigo-600/30 bg-indigo-600/10 px-3 text-xs font-bold text-indigo-700 hover:bg-indigo-600 hover:text-white active:scale-[0.98] dark:border-indigo-400/30 dark:bg-indigo-400/10 dark:text-indigo-300 dark:hover:bg-indigo-600 dark:hover:text-white"
                            @click="closeMobileMenu"
                        >
                            <Lock class="size-3.5" />
                            <span>Kedinasan</span>
                        </a>
                    </div>
                </div>
            </div>
        </transition>
    </header>
</template>

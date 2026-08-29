<script setup lang="ts">
import { Landmark, ShieldCheck } from '@lucide/vue';

defineProps<{ preview?: boolean }>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/80 to-violet-50 p-6 shadow-sm sm:p-8 dark:border-blue-950 dark:from-slate-950 dark:via-blue-950/35 dark:to-violet-950/30"
        aria-labelledby="approval-page-title"
    >
        <div
            class="absolute -top-24 -right-10 size-64 rounded-full bg-violet-500/10 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="absolute -bottom-24 left-1/3 size-56 rounded-full bg-blue-500/10 blur-3xl"
            aria-hidden="true"
        />

        <div
            class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="mb-5 flex items-center gap-3">
                    <span
                        class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600 text-white shadow-sm shadow-blue-600/20"
                    >
                        <Landmark class="size-6" aria-hidden="true" />
                    </span>
                    <span
                        v-if="preview"
                        class="rounded-full border border-violet-300 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-800 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-200"
                    >
                        Pratinjau lokal
                    </span>
                </div>
                <p
                    class="mb-2 text-xs font-semibold tracking-[0.18em] text-blue-700 uppercase dark:text-blue-300"
                >
                    Meja Kepala Bagian Umum
                </p>
                <h1
                    id="approval-page-title"
                    class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    Persetujuan surat
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base dark:text-slate-300"
                >
                    Tinjau pengajuan yang telah dinyatakan lengkap oleh petugas,
                    lalu tentukan keputusan administratif resminya.
                </p>
            </div>

            <div
                class="flex max-w-sm items-start gap-3 rounded-2xl border border-blue-200/80 bg-white/80 p-4 text-sm shadow-xs backdrop-blur dark:border-blue-900 dark:bg-slate-950/60"
            >
                <ShieldCheck
                    class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300"
                    aria-hidden="true"
                />
                <p class="leading-6 text-muted-foreground">
                    Surat yang belum lolos pemeriksaan petugas tidak ditampilkan
                    pada meja keputusan ini.
                </p>
            </div>
        </div>
    </header>
</template>

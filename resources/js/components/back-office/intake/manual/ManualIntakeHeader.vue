<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpenCheck, FilePlus2, ShieldCheck } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    registerUrl: string;
    preview?: boolean;
    mode?: 'create' | 'revision';
}>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/70 to-amber-50/80 p-5 shadow-sm sm:p-7 dark:border-emerald-950 dark:from-slate-950 dark:via-emerald-950/25 dark:to-amber-950/20"
        aria-labelledby="manual-intake-title"
    >
        <div
            class="absolute -top-20 -right-12 size-56 rounded-full bg-amber-400/10 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="absolute -bottom-24 left-1/4 size-52 rounded-full bg-emerald-500/10 blur-3xl"
            aria-hidden="true"
        />

        <div
            class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="mb-5 flex flex-wrap items-center gap-3">
                    <span
                        class="flex size-12 items-center justify-center rounded-2xl bg-emerald-700 text-white shadow-sm shadow-emerald-700/20 dark:bg-emerald-500 dark:text-emerald-950"
                    >
                        <FilePlus2 class="size-6" aria-hidden="true" />
                    </span>
                    <Badge
                        v-if="preview"
                        variant="outline"
                        class="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        Mode pratinjau
                    </Badge>
                </div>
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-emerald-800 uppercase dark:text-emerald-300"
                >
                    Kanal surat fisik
                </p>
                <h1
                    id="manual-intake-title"
                    class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    {{
                        mode === 'revision'
                            ? 'Perbaiki surat masuk manual'
                            : 'Catat surat masuk manual'
                    }}
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base dark:text-slate-300"
                >
                    {{
                        mode === 'revision'
                            ? 'Tindak lanjuti catatan Kabag Umum, lalu ajukan kembali tanpa mengubah histori pemeriksaan.'
                            : 'Salin identitas surat, unggah hasil scan, lalu pastikan pemeriksaan lengkap sebelum diajukan kepada Kepala Bagian Umum.'
                    }}
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                <div
                    class="flex max-w-sm items-start gap-3 rounded-2xl border border-emerald-200/80 bg-white/80 p-4 text-sm shadow-xs dark:border-emerald-900 dark:bg-slate-950/60"
                >
                    <ShieldCheck
                        class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <p class="leading-6 text-muted-foreground">
                        Pencatatan ini belum menjadi surat resmi. Nomor agenda
                        tetap disahkan oleh Kabag Umum.
                    </p>
                </div>
                <Button
                    as-child
                    variant="outline"
                    class="min-h-11 justify-between rounded-xl bg-background/80 px-4"
                >
                    <Link :href="registerUrl">
                        <span class="inline-flex items-center gap-2">
                            <BookOpenCheck class="size-4" aria-hidden="true" />
                            Buka buku surat masuk
                        </span>
                        <ArrowLeft
                            class="size-4 rotate-180"
                            aria-hidden="true"
                        />
                    </Link>
                </Button>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { Archive, LockKeyhole, ShieldCheck } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

defineProps<{ preview?: boolean }>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/70 to-violet-50/80 p-5 shadow-sm sm:p-7 dark:border-blue-950 dark:from-slate-950 dark:via-blue-950/35 dark:to-violet-950/30"
    >
        <div
            class="pointer-events-none absolute -top-16 -right-12 size-48 rounded-full bg-violet-300/20 blur-3xl dark:bg-violet-600/10"
            aria-hidden="true"
        />

        <div
            class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="flex size-10 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm shadow-blue-600/20"
                    >
                        <Archive class="size-5" aria-hidden="true" />
                    </span>
                    <Badge
                        variant="outline"
                        class="border-blue-200 bg-white/75 text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300"
                    >
                        Arsip privat internal
                    </Badge>
                    <Badge v-if="preview" variant="secondary">
                        Mode pratinjau
                    </Badge>
                </div>

                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Arsip Dokumen Surat
                    </h1>
                    <p
                        class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground"
                    >
                        Temukan surat resmi, periksa versi dokumen yang pernah
                        tercatat, dan buka metadata integritasnya dari satu
                        tempat.
                    </p>
                </div>
            </div>

            <div
                class="grid gap-2 text-xs text-muted-foreground sm:grid-cols-2 lg:max-w-md"
            >
                <span
                    class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-white/80 bg-white/70 px-3 dark:border-slate-800 dark:bg-slate-950/50"
                >
                    <ShieldCheck
                        class="size-4 text-emerald-600"
                        aria-hidden="true"
                    />
                    Setiap versi memiliki sidik jari SHA-256
                </span>
                <span
                    class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-white/80 bg-white/70 px-3 dark:border-slate-800 dark:bg-slate-950/50"
                >
                    <LockKeyhole
                        class="size-4 text-violet-600"
                        aria-hidden="true"
                    />
                    Akses berkas mengikuti kewenangan surat
                </span>
            </div>
        </div>
    </header>
</template>

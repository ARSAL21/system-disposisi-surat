<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    FilePlus2,
    Fingerprint,
    ShieldCheck,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    createManualUrl: string | null;
    preview?: boolean;
}>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-amber-100 bg-gradient-to-br from-white via-amber-50/65 to-emerald-50/75 p-5 shadow-sm sm:p-7 dark:border-amber-950 dark:from-slate-950 dark:via-amber-950/20 dark:to-emerald-950/25"
        aria-labelledby="incoming-register-title"
    >
        <div
            class="absolute -top-24 right-1/4 size-56 rounded-full bg-amber-400/10 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="absolute -right-16 -bottom-24 size-64 rounded-full bg-emerald-500/10 blur-3xl"
            aria-hidden="true"
        />

        <div
            class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="mb-5 flex flex-wrap items-center gap-3">
                    <span
                        class="flex size-12 items-center justify-center rounded-2xl bg-amber-600 text-white shadow-sm shadow-amber-600/20 dark:bg-amber-400 dark:text-amber-950"
                    >
                        <BookOpenCheck class="size-6" aria-hidden="true" />
                    </span>
                    <Badge
                        v-if="preview"
                        variant="outline"
                        class="border-amber-300 bg-white/75 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        Mode pratinjau
                    </Badge>
                </div>
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-amber-800 uppercase dark:text-amber-300"
                >
                    Register administrasi terpadu
                </p>
                <h1
                    id="incoming-register-title"
                    class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    Buku Agenda Surat Masuk
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base dark:text-slate-300"
                >
                    Satu catatan resmi untuk surat dari portal online maupun
                    surat fisik yang diterima langsung oleh Bagian Umum.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div
                    class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-white/75 px-4 py-3 text-sm dark:border-emerald-900 dark:bg-slate-950/55"
                >
                    <ShieldCheck
                        class="size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <div>
                        <p class="font-semibold">Jejak resmi terjaga</p>
                        <p class="text-xs text-muted-foreground">
                            Nomor agenda unik per tahun
                        </p>
                    </div>
                    <Fingerprint
                        class="ml-auto size-4 text-muted-foreground"
                        aria-hidden="true"
                    />
                </div>
                <Button
                    v-if="createManualUrl"
                    as-child
                    class="min-h-12 rounded-xl bg-emerald-700 px-5 text-white shadow-sm hover:bg-emerald-800 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400"
                >
                    <Link :href="createManualUrl">
                        <FilePlus2 class="size-4" aria-hidden="true" />
                        Catat surat fisik
                    </Link>
                </Button>
            </div>
        </div>
    </header>
</template>

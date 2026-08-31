<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Inbox, ShieldCheck } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{ preview?: boolean }>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/75 to-emerald-50/70 p-5 shadow-sm sm:p-7 dark:border-blue-950 dark:from-slate-950 dark:via-blue-950/35 dark:to-emerald-950/20"
        aria-labelledby="disposition-inbox-title"
    >
        <div
            class="pointer-events-none absolute -top-20 -right-16 size-56 rounded-full bg-emerald-400/15 blur-3xl dark:bg-emerald-600/10"
            aria-hidden="true"
        />
        <div
            class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-700 to-emerald-700 text-white shadow-sm"
                    >
                        <Inbox class="size-5" aria-hidden="true" />
                    </span>
                    <Badge
                        variant="outline"
                        class="border-blue-200 bg-white/80 text-blue-800 dark:border-blue-800 dark:bg-blue-950/55 dark:text-blue-200"
                    >
                        M6 · Position-based routing
                    </Badge>
                    <Badge v-if="preview" variant="secondary">
                        Pratinjau lokal
                    </Badge>
                </div>
                <p
                    class="mt-5 text-xs font-semibold tracking-[0.17em] text-blue-700 uppercase dark:text-blue-300"
                >
                    Ruang kerja Asisten
                </p>
                <h1
                    id="disposition-inbox-title"
                    class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    Inbox disposisi
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                >
                    Tinjau surat dan instruksi yang dikirim secara resmi oleh
                    Wali Kota atau Sekretaris Daerah kepada jabatan aktif Anda.
                </p>
            </div>

            <div class="grid max-w-md gap-3">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-white/80 bg-white/75 p-4 text-sm shadow-xs backdrop-blur dark:border-slate-800 dark:bg-slate-950/55"
                >
                    <ShieldCheck
                        class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <p class="leading-6 text-muted-foreground">
                        Inbox dibatasi oleh Position Assignment aktif. Disposisi
                        jabatan Asisten lain tidak ditampilkan.
                    </p>
                </div>
                <Button
                    v-if="preview"
                    as-child
                    variant="outline"
                    class="min-h-11 justify-between bg-background/75"
                >
                    <Link href="/back-office/previews/executive-inbox/routes/503">
                        Lihat ruang keputusan pimpinan
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </div>
    </header>
</template>

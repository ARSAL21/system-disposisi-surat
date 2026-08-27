<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, FilePlus2, Files } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import publicRoutes from '@/routes/public';

defineProps<{ userName: string }>();
</script>

<template>
    <section
        class="relative overflow-hidden rounded-3xl border bg-card px-5 py-6 shadow-sm sm:px-7 sm:py-8"
    >
        <div
            class="pointer-events-none absolute top-0 right-0 h-full w-2/5 bg-[radial-gradient(circle_at_top_right,color-mix(in_srgb,var(--color-primary)_16%,transparent),transparent_68%)]"
            aria-hidden="true"
        />
        <div class="relative max-w-3xl">
            <p class="text-sm font-semibold text-primary">
                Portal Surat Publik
            </p>
            <h1
                class="mt-2 text-2xl leading-tight font-semibold tracking-tight sm:text-3xl"
            >
                Selamat datang, {{ userName }}.
            </h1>
            <p
                class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
            >
                Kelola draft, unggah dokumen PDF, dan pantau surat yang sudah
                Anda kirim kepada Bagian Umum.
            </p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <Button
                    class="min-h-11 cursor-pointer rounded-xl px-5"
                    as-child
                >
                    <Link :href="publicRoutes.submissions.create()">
                        <FilePlus2 class="size-4" />
                        Buat surat baru
                        <ArrowRight class="size-4" />
                    </Link>
                </Button>
                <Button
                    variant="outline"
                    class="min-h-11 cursor-pointer rounded-xl bg-card px-5"
                    as-child
                >
                    <Link :href="publicRoutes.submissions.index()">
                        <Files class="size-4" />
                        Lihat semua surat
                    </Link>
                </Button>
            </div>
        </div>
    </section>
</template>

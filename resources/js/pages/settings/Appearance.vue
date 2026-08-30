<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Palette, Sparkles } from '@lucide/vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Appearance settings" />

    <h1 class="sr-only">Appearance settings</h1>

    <div class="space-y-8">
        <!-- Header info -->
        <div class="flex items-center gap-3 border-b border-border/60 pb-4">
            <div
                class="flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300"
            >
                <Palette class="size-4.5" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-foreground">
                    Tema & Tampilan Antarmuka
                </h3>
                <p class="text-xs text-muted-foreground">
                    Pilih skema warna yang paling sesuai dengan lingkungan kerja Anda
                </p>
            </div>
        </div>

        <!-- Interactive Theme Mockups -->
        <div class="space-y-4">
            <AppearanceTabs />
        </div>

        <!-- Real-time sync note -->
        <div
            class="flex items-center gap-3 rounded-xl border border-indigo-100/80 bg-gradient-to-r from-indigo-50/40 via-white to-neutral-50/40 p-4 text-xs text-muted-foreground dark:border-indigo-900/30 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20"
        >
            <Sparkles class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
            <p>
                Preferensi tema akan otomatis disimpan dan diterapkan secara instan ke seluruh halaman tanpa perlu memuat ulang.
            </p>
        </div>
    </div>
</template>

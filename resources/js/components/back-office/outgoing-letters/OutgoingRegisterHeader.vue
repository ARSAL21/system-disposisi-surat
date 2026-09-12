<script setup lang="ts">
import { BookOpenCheck, CircleHelp, PrinterCheck } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
</script>

<template>
    <header class="overflow-hidden rounded-3xl border bg-card shadow-sm">
        <div
            class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end"
        >
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="outline" class="rounded-full">
                        <BookOpenCheck class="mr-1 size-3.5" />
                        Buku administrasi resmi
                    </Badge>
                    <Badge class="rounded-full bg-indigo-600">M8.3 + M10</Badge>
                </div>
                <h1
                    class="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    Register Surat Keluar
                </h1>
                <p
                    class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                >
                    Catat nomor, pantau pengesahan Sekda, verifikasi dokumen,
                    lalu catat pengiriman dari satu meja kerja yang sama.
                </p>
            </div>
            <div
                class="flex items-center gap-3 rounded-2xl border bg-muted/30 p-4 lg:max-w-sm"
            >
                <span
                    class="grid size-11 shrink-0 place-items-center rounded-xl bg-indigo-600 text-white"
                >
                    <PrinterCheck class="size-5" aria-hidden="true" />
                </span>
                <div>
                    <p class="text-sm font-semibold">
                        Dokumen final tetap privat
                    </p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        Balasan dan surat mandiri tetap tersimpan privat selama
                        diproses.
                    </p>
                </div>
                <CircleHelp
                    class="ml-auto size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
            </div>
        </div>
        <div
            class="grid grid-cols-2 border-t bg-muted/20 sm:grid-cols-5"
            aria-label="Urutan penerbitan"
        >
            <div
                v-for="(label, index) in [
                    'Persetujuan',
                    'Nomor',
                    'Pengesahan Sekda',
                    'Verifikasi',
                    'Pengiriman',
                ]"
                :key="label"
                class="relative flex items-center gap-2 border-r px-4 py-3 text-xs font-medium last:border-r-0"
            >
                <span
                    class="grid size-6 place-items-center rounded-full border bg-background font-mono text-[10px] font-bold text-indigo-700 dark:text-indigo-300"
                    >{{ index + 1 }}</span
                >
                {{ label }}
            </div>
        </div>
    </header>
</template>

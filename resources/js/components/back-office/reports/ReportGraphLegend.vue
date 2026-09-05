<script setup lang="ts">
import {
    AlertTriangle,
    CheckCircle2,
    CircleDotDashed,
    MousePointerClick,
    Play,
} from '@lucide/vue';
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border bg-background/85 px-3 py-2 text-[11px] text-muted-foreground shadow-xs backdrop-blur"
        aria-label="Keterangan status peta"
    >
        <span class="inline-flex items-center gap-1.5">
            <CircleDotDashed class="size-3.5 text-amber-500" /> Menunggu
        </span>
        <span class="inline-flex items-center gap-1.5">
            <Play class="size-3.5 text-sky-500" /> Dikerjakan
        </span>
        <span class="inline-flex items-center gap-1.5">
            <CheckCircle2 class="size-3.5 text-emerald-500" /> Selesai
        </span>
        <span class="inline-flex items-center gap-1.5">
            <AlertTriangle class="size-3.5 text-amber-600" /> Perlu perhatian
        </span>
        <span class="ml-auto hidden items-center gap-1.5 sm:inline-flex">
            <MousePointerClick class="size-3.5" /> Klik jabatan untuk melihat
            detail
        </span>
    </div>
</template>

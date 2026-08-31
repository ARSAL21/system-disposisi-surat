<script setup lang="ts">
import { ArchiveX, RotateCcw } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineEmits<{ reset: [] }>();
</script>

<template>
    <div
        class="flex min-h-72 flex-col items-center justify-center px-6 py-12 text-center"
    >
        <span
            class="flex size-14 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
        >
            <ArchiveX class="size-7" aria-hidden="true" />
        </span>
        <h2 class="mt-4 text-lg font-semibold">Arsip tidak ditemukan</h2>
        <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
            Tidak ada surat resmi yang cocok dengan kata kunci, status, atau
            rentang tanggal yang dipilih.
        </p>
        <Button
            type="button"
            variant="outline"
            class="mt-5 min-h-11"
            @click="$emit('reset')"
        >
            <RotateCcw class="size-4" aria-hidden="true" />
            Atur ulang filter
        </Button>
    </div>
</template>

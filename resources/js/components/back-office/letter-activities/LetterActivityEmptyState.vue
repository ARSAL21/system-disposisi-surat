<script setup lang="ts">
import { CalendarSearch, RotateCcw } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    filtered: boolean;
}>();
defineEmits<{
    reset: [];
}>();
</script>

<template>
    <div class="flex flex-col items-center px-6 py-16 text-center">
        <span
            class="flex size-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-700 dark:text-indigo-300"
        >
            <CalendarSearch class="size-6" aria-hidden="true" />
        </span>
        <h3 class="mt-5 text-lg font-semibold text-slate-950 dark:text-white">
            {{
                filtered
                    ? 'Aktivitas yang dicari tidak ditemukan'
                    : 'Belum ada aktivitas surat'
            }}
        </h3>
        <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
            {{
                filtered
                    ? 'Ubah jenis aktivitas, pelaksana, surat, atau rentang tanggal untuk memperluas hasil.'
                    : 'Aktivitas intake dan registrasi pada periode ini akan tampil di sini.'
            }}
        </p>
        <Button
            v-if="filtered"
            type="button"
            variant="outline"
            class="mt-5 min-h-11"
            @click="$emit('reset')"
        >
            <RotateCcw class="size-4" aria-hidden="true" />
            Kembali ke hari ini
        </Button>
    </div>
</template>

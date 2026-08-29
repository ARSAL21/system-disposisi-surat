<script setup lang="ts">
import { BadgeCheck, SearchX } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{ history: boolean }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <div class="flex flex-col items-center px-6 py-16 text-center">
        <span
            class="flex size-14 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
        >
            <SearchX v-if="history" class="size-7" aria-hidden="true" />
            <BadgeCheck v-else class="size-7" aria-hidden="true" />
        </span>
        <h2 class="mt-5 text-lg font-semibold">
            {{
                history
                    ? 'Riwayat keputusan tidak ditemukan'
                    : 'Tidak ada surat yang menunggu keputusan'
            }}
        </h2>
        <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
            {{
                history
                    ? 'Ubah pencarian atau rentang tanggal untuk melihat riwayat lainnya.'
                    : 'Surat akan muncul setelah dinyatakan lengkap oleh petugas Bagian Umum.'
            }}
        </p>
        <Button
            v-if="history"
            type="button"
            variant="outline"
            class="mt-5 min-h-11"
            @click="$emit('reset')"
        >
            Atur ulang penyaring
        </Button>
    </div>
</template>

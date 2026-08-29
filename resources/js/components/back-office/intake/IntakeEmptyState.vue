<script setup lang="ts">
import { Inbox } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineEmits<{ reset: [] }>();
</script>

<template>
    <div class="flex flex-col items-center px-6 py-16 text-center">
        <span
            class="flex size-14 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
        >
            <Inbox class="size-7" aria-hidden="true" />
        </span>
        <h2 class="mt-5 text-lg font-semibold">
            Tidak ada pengajuan surat yang cocok
        </h2>
        <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
            Ubah kata pencarian atau atur ulang penyaring untuk melihat antrean
            lainnya.
        </p>
        <Button
            type="button"
            variant="outline"
            class="mt-5 min-h-11"
            @click="$emit('reset')"
        >
            Atur ulang penyaring
        </Button>
    </div>
</template>

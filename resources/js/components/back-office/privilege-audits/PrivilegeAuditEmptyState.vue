<script setup lang="ts">
import { FileSearch } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{ filtered: boolean }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <div class="px-5 py-16 text-center">
        <span
            class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-700 dark:text-indigo-300"
        >
            <FileSearch class="size-7" aria-hidden="true" />
        </span>
        <p class="mt-4 font-semibold">Catatan audit tidak ditemukan</p>
        <p
            class="mx-auto mt-1 max-w-md text-sm leading-6 text-muted-foreground"
        >
            {{
                filtered
                    ? 'Tidak ada catatan yang sesuai dengan kombinasi filter saat ini.'
                    : 'Belum ada perubahan privilege yang dapat ditampilkan.'
            }}
        </p>
        <Button
            v-if="filtered"
            type="button"
            variant="outline"
            class="mt-5 min-h-11"
            @click="$emit('reset')"
        >
            Reset filter
        </Button>
    </div>
</template>

<script setup lang="ts">
import { BookOpen, SearchX } from '@lucide/vue';
import IncomingRegisterCards from '@/components/back-office/incoming-register/IncomingRegisterCards.vue';
import IncomingRegisterTable from '@/components/back-office/incoming-register/IncomingRegisterTable.vue';
import { Button } from '@/components/ui/button';
import type { IncomingRegisterItem } from '@/types';

defineProps<{ letters: IncomingRegisterItem[] }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <div
            class="flex flex-col gap-1 border-b bg-muted/20 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <BookOpen
                    class="size-5 text-amber-700 dark:text-amber-300"
                    aria-hidden="true"
                />
                <div>
                    <h2 class="text-sm font-semibold">Catatan agenda</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Diurutkan dari waktu penerimaan terbaru.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="letters.length === 0"
            class="flex min-h-72 flex-col items-center justify-center px-5 py-12 text-center"
        >
            <span
                class="flex size-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            >
                <SearchX class="size-6" aria-hidden="true" />
            </span>
            <h3 class="mt-4 font-semibold">Tidak ada surat yang cocok</h3>
            <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                Ubah kata pencarian atau hapus beberapa filter untuk melihat
                catatan agenda lainnya.
            </p>
            <Button
                type="button"
                variant="outline"
                class="mt-5 min-h-11 cursor-pointer"
                @click="$emit('reset')"
            >
                Tampilkan seluruh surat
            </Button>
        </div>
        <template v-else>
            <IncomingRegisterTable :letters="letters" />
            <IncomingRegisterCards :letters="letters" />
            <slot name="pagination" />
        </template>
    </section>
</template>

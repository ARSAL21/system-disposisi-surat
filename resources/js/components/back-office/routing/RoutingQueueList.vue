<script setup lang="ts">
import { SearchX } from '@lucide/vue';
import RoutingQueueCards from '@/components/back-office/routing/RoutingQueueCards.vue';
import RoutingQueueTable from '@/components/back-office/routing/RoutingQueueTable.vue';
import { Button } from '@/components/ui/button';
import type { LetterRoutingItem } from '@/types';

defineProps<{ letters: LetterRoutingItem[] }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <div
            v-if="letters.length === 0"
            class="flex min-h-72 flex-col items-center justify-center px-5 py-12 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            >
                <SearchX class="size-5" aria-hidden="true" />
            </span>
            <h2 class="mt-4 font-semibold">Surat tidak ditemukan</h2>
            <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                Ubah kata pencarian atau tampilkan kembali seluruh tahap
                routing.
            </p>
            <Button
                type="button"
                variant="outline"
                class="mt-5 min-h-11"
                @click="$emit('reset')"
            >
                Tampilkan semua surat
            </Button>
        </div>
        <template v-else>
            <RoutingQueueTable :letters="letters" />
            <RoutingQueueCards :letters="letters" />
            <slot name="pagination" />
        </template>
    </section>
</template>

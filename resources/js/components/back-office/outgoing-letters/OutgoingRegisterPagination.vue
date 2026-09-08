<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { OutgoingLetterPagination } from '@/types';

defineProps<{ pagination: OutgoingLetterPagination }>();
</script>

<template>
    <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
        <p class="text-muted-foreground">Menampilkan {{ pagination.from }}–{{ pagination.to }} dari {{ pagination.total }} surat</p>
        <div class="flex items-center gap-2">
            <Button variant="outline" size="sm" :disabled="!pagination.previous_url" as-child>
                <Link v-if="pagination.previous_url" :href="pagination.previous_url" preserve-scroll><ChevronLeft class="size-4" /> Sebelumnya</Link>
                <span v-else><ChevronLeft class="size-4" /> Sebelumnya</span>
            </Button>
            <span class="px-2 text-xs font-semibold tabular-nums">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <Button variant="outline" size="sm" :disabled="!pagination.next_url" as-child>
                <Link v-if="pagination.next_url" :href="pagination.next_url" preserve-scroll>Berikutnya <ChevronRight class="size-4" /></Link>
                <span v-else>Berikutnya <ChevronRight class="size-4" /></span>
            </Button>
        </div>
    </div>
</template>

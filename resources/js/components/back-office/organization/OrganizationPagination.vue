<script setup lang="ts" generic="T">
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

const props = defineProps<{ pagination: Paginated<T>; processing: boolean }>();
const emit = defineEmits<{ page: [page: number] }>();
function goTo(page: number): void {
    if (
        !props.processing &&
        page > 0 &&
        page <= props.pagination.meta.last_page
    ) {
        emit('page', page);
    }
}
</script>

<template>
    <nav
        v-if="pagination.meta.last_page > 1"
        class="flex flex-col gap-3 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
        aria-label="Paginasi organisasi"
    >
        <p class="text-sm text-muted-foreground">
            <strong class="font-medium text-foreground"
                >{{ pagination.meta.from }}–{{ pagination.meta.to }}</strong
            >
            dari
            <strong class="font-medium text-foreground">{{
                pagination.meta.total
            }}</strong>
            data
        </p>
        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                class="min-h-11"
                :disabled="processing || pagination.meta.current_page === 1"
                @click="goTo(pagination.meta.current_page - 1)"
            >
                <ArrowLeft class="size-4" aria-hidden="true" /> Sebelumnya
            </Button>
            <span class="px-2 text-sm tabular-nums"
                >{{ pagination.meta.current_page }}/{{
                    pagination.meta.last_page
                }}</span
            >
            <Button
                variant="outline"
                class="min-h-11"
                :disabled="
                    processing ||
                    pagination.meta.current_page === pagination.meta.last_page
                "
                @click="goTo(pagination.meta.current_page + 1)"
            >
                Berikutnya <ArrowRight class="size-4" aria-hidden="true" />
            </Button>
        </div>
    </nav>
</template>

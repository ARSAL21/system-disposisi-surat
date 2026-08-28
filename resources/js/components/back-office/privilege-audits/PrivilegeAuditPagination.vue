<script setup lang="ts">
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { PaginatedPrivilegeAudits } from '@/types';

const props = defineProps<{
    pagination: PaginatedPrivilegeAudits;
    processing: boolean;
}>();
const emit = defineEmits<{ page: [page: number] }>();

function goTo(page: number): void {
    if (
        props.processing ||
        page < 1 ||
        page > props.pagination.meta.last_page
    ) {
        return;
    }

    emit('page', page);
}
</script>

<template>
    <nav
        v-if="pagination.meta.last_page > 1"
        class="flex flex-col gap-4 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        aria-label="Paginasi audit privilege"
    >
        <p class="text-sm text-muted-foreground" aria-live="polite">
            <span class="font-medium text-foreground tabular-nums">
                {{ pagination.meta.from }}–{{ pagination.meta.to }}
            </span>
            dari
            <span class="font-medium text-foreground tabular-nums">
                {{ pagination.meta.total }}
            </span>
            catatan
        </p>
        <div class="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
                :disabled="processing || pagination.meta.current_page === 1"
                @click="goTo(pagination.meta.current_page - 1)"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Sebelumnya
            </Button>
            <span class="px-2 text-sm font-medium tabular-nums">
                {{ pagination.meta.current_page }} /
                {{ pagination.meta.last_page }}
            </span>
            <Button
                type="button"
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
                :disabled="
                    processing ||
                    pagination.meta.current_page === pagination.meta.last_page
                "
                @click="goTo(pagination.meta.current_page + 1)"
            >
                Berikutnya
                <ArrowRight class="size-4" aria-hidden="true" />
            </Button>
        </div>
    </nav>
</template>

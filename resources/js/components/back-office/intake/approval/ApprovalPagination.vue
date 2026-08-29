<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { ApprovalPagination } from '@/types';

defineProps<{ pagination: ApprovalPagination }>();
</script>

<template>
    <nav
        v-if="pagination.last_page > 1"
        class="flex flex-col gap-4 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        aria-label="Navigasi halaman persetujuan surat"
    >
        <p class="text-sm text-muted-foreground" aria-live="polite">
            Menampilkan
            <span class="font-medium text-foreground tabular-nums">
                {{ pagination.from }}–{{ pagination.to }}
            </span>
            dari
            <span class="font-medium text-foreground tabular-nums">
                {{ pagination.total }}
            </span>
            surat
        </p>
        <div class="flex items-center gap-2">
            <Button
                v-if="pagination.previous_url"
                as-child
                variant="outline"
                class="min-h-11"
            >
                <Link :href="pagination.previous_url" preserve-state>
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Sebelumnya
                </Link>
            </Button>
            <span class="px-2 text-sm font-medium tabular-nums">
                {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <Button
                v-if="pagination.next_url"
                as-child
                variant="outline"
                class="min-h-11"
            >
                <Link :href="pagination.next_url" preserve-state>
                    Berikutnya
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
        </div>
    </nav>
</template>

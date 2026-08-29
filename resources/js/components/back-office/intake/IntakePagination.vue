<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { IntakePagination } from '@/types';

defineProps<{ pagination: IntakePagination }>();
</script>

<template>
    <nav
        class="flex flex-col gap-4 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        aria-label="Navigasi halaman antrean pemeriksaan"
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
            pengajuan surat
        </p>
        <div class="flex items-center gap-2">
            <Button
                v-if="pagination.previous_url"
                as-child
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
            >
                <Link
                    :href="pagination.previous_url"
                    preserve-scroll
                    preserve-state
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Sebelumnya
                </Link>
            </Button>
            <Button
                v-else
                type="button"
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
                disabled
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Sebelumnya
            </Button>
            <span class="px-2 text-sm font-medium tabular-nums">
                {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <Button
                v-if="pagination.next_url"
                as-child
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
            >
                <Link
                    :href="pagination.next_url"
                    preserve-scroll
                    preserve-state
                >
                    Berikutnya
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
            <Button
                v-else
                type="button"
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
                disabled
            >
                Berikutnya
                <ArrowRight class="size-4" aria-hidden="true" />
            </Button>
        </div>
    </nav>
</template>

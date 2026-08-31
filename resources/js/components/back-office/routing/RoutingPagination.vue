<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { RoutingPagination } from '@/types';

defineProps<{ pagination: RoutingPagination }>();
</script>

<template>
    <footer
        class="flex flex-col gap-3 border-t px-4 py-4 text-sm sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-muted-foreground" aria-live="polite">
            <template v-if="pagination.total > 0">
                Menampilkan
                <span class="font-semibold text-foreground tabular-nums">
                    {{ pagination.from }}–{{ pagination.to }}
                </span>
                dari
                <span class="font-semibold text-foreground tabular-nums">
                    {{ pagination.total }}
                </span>
                surat
            </template>
            <template v-else>Belum ada surat</template>
        </p>

        <div class="flex gap-2">
            <Button
                v-if="pagination.previous_url"
                as-child
                variant="outline"
                class="min-h-11"
            >
                <Link :href="pagination.previous_url" preserve-scroll>
                    <ChevronLeft class="size-4" aria-hidden="true" />
                    Sebelumnya
                </Link>
            </Button>
            <Button v-else variant="outline" class="min-h-11" disabled>
                <ChevronLeft class="size-4" aria-hidden="true" />
                Sebelumnya
            </Button>

            <Button
                v-if="pagination.next_url"
                as-child
                variant="outline"
                class="min-h-11"
            >
                <Link :href="pagination.next_url" preserve-scroll>
                    Berikutnya
                    <ChevronRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
            <Button v-else variant="outline" class="min-h-11" disabled>
                Berikutnya
                <ChevronRight class="size-4" aria-hidden="true" />
            </Button>
        </div>
    </footer>
</template>

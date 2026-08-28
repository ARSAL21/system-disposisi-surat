<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { PaginatedAuthorizationUsers } from '@/types';

defineProps<{ pagination: PaginatedAuthorizationUsers }>();
</script>

<template>
    <nav
        v-if="pagination.meta.last_page > 1"
        class="flex flex-col gap-4 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        aria-label="Paginasi akun internal"
    >
        <p class="text-sm text-muted-foreground">
            <span class="font-medium text-foreground tabular-nums">
                {{ pagination.meta.from }}–{{ pagination.meta.to }}
            </span>
            dari
            <span class="font-medium text-foreground tabular-nums">
                {{ pagination.meta.total }}
            </span>
            akun
        </p>
        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
                :disabled="!pagination.links.prev"
                as-child
            >
                <Link
                    v-if="pagination.links.prev"
                    :href="pagination.links.prev"
                    preserve-scroll
                    preserve-state
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Sebelumnya
                </Link>
                <span v-else>
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Sebelumnya
                </span>
            </Button>
            <span class="px-2 text-sm font-medium tabular-nums">
                {{ pagination.meta.current_page }} /
                {{ pagination.meta.last_page }}
            </span>
            <Button
                variant="outline"
                class="min-h-11 flex-1 sm:flex-none"
                :disabled="!pagination.links.next"
                as-child
            >
                <Link
                    v-if="pagination.links.next"
                    :href="pagination.links.next"
                    preserve-scroll
                    preserve-state
                >
                    Berikutnya
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
                <span v-else>
                    Berikutnya
                    <ArrowRight class="size-4" aria-hidden="true" />
                </span>
            </Button>
        </div>
    </nav>
</template>

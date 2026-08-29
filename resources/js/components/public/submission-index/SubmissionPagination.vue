<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { PaginatedSubmissions } from '@/types';

defineProps<{ pagination: PaginatedSubmissions }>();
</script>

<template>
    <nav
        v-if="pagination.meta.last_page > 1"
        class="flex flex-col gap-4 rounded-2xl border bg-card px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        aria-label="Navigasi halaman pengajuan surat"
    >
        <p class="text-sm text-muted-foreground">
            Menampilkan
            <span class="font-semibold text-foreground"
                >{{ pagination.meta.from }}–{{ pagination.meta.to }}</span
            >
            dari
            <span class="font-semibold text-foreground">{{
                pagination.meta.total
            }}</span>
            pengajuan surat
        </p>
        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                class="min-h-11 flex-1 cursor-pointer rounded-xl sm:flex-none"
                :disabled="!pagination.links.prev"
                as-child
            >
                <Link
                    v-if="pagination.links.prev"
                    :href="pagination.links.prev"
                    preserve-scroll
                >
                    <ArrowLeft class="size-4" />Sebelumnya
                </Link>
                <span v-else><ArrowLeft class="size-4" />Sebelumnya</span>
            </Button>
            <span class="px-2 text-sm font-medium tabular-nums"
                >{{ pagination.meta.current_page }} /
                {{ pagination.meta.last_page }}</span
            >
            <Button
                variant="outline"
                class="min-h-11 flex-1 cursor-pointer rounded-xl sm:flex-none"
                :disabled="!pagination.links.next"
                as-child
            >
                <Link
                    v-if="pagination.links.next"
                    :href="pagination.links.next"
                    preserve-scroll
                >
                    Berikutnya<ArrowRight class="size-4" />
                </Link>
                <span v-else>Berikutnya<ArrowRight class="size-4" /></span>
            </Button>
        </div>
    </nav>
</template>

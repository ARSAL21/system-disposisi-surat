<script setup lang="ts">
import { Inbox, RotateCcw } from '@lucide/vue';
import ExecutiveInboxCards from '@/components/back-office/routing/ExecutiveInboxCards.vue';
import ExecutiveInboxTable from '@/components/back-office/routing/ExecutiveInboxTable.vue';
import { Button } from '@/components/ui/button';
import type { ExecutiveInboxItem } from '@/types';

defineProps<{
    routes: ExecutiveInboxItem[];
    viewMode?: 'cards' | 'table';
}>();

defineEmits<{ reset: [] }>();
</script>

<template>
    <section class="space-y-4">
        <!-- Empty State -->
        <div
            v-if="routes.length === 0"
            class="flex min-h-80 flex-col items-center justify-center rounded-3xl border border-dashed border-border/80 bg-card/60 p-8 text-center backdrop-blur-sm dark:border-border/60 dark:bg-slate-900/60"
        >
            <div
                class="flex size-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400"
            >
                <Inbox class="size-7" />
            </div>
            <h2
                class="mt-4 font-['Syne',sans-serif] text-lg font-bold text-foreground"
            >
                Tidak Ada Surat di Inbox Pimpinan
            </h2>
            <p
                class="mt-1.5 max-w-md text-xs leading-relaxed text-muted-foreground sm:text-sm"
            >
                Tidak ditemukan berkas naskah dinas yang sesuai dengan parameter
                pencarian, filter fase, atau rentang tanggal ini.
            </p>
            <Button
                type="button"
                variant="outline"
                class="mt-5 h-10 rounded-2xl border-border/80 px-4 text-xs font-bold"
                @click="$emit('reset')"
            >
                <RotateCcw class="mr-1.5 size-3.5" />
                <span>Tampilkan Seluruh Inbox</span>
            </Button>
        </div>

        <!-- Active Items List / Table -->
        <template v-else>
            <ExecutiveInboxTable v-if="viewMode === 'table'" :routes="routes" />
            <ExecutiveInboxCards v-else :routes="routes" />

            <div class="pt-2">
                <slot name="pagination" />
            </div>
        </template>
    </section>
</template>

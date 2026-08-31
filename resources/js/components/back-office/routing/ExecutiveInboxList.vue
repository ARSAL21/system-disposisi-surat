<script setup lang="ts">
import { Inbox } from '@lucide/vue';
import ExecutiveInboxCards from '@/components/back-office/routing/ExecutiveInboxCards.vue';
import ExecutiveInboxTable from '@/components/back-office/routing/ExecutiveInboxTable.vue';
import { Button } from '@/components/ui/button';
import type { ExecutiveInboxItem } from '@/types';

defineProps<{ routes: ExecutiveInboxItem[] }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <div
            v-if="routes.length === 0"
            class="flex min-h-72 flex-col items-center justify-center px-5 py-12 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            >
                <Inbox class="size-5" aria-hidden="true" />
            </span>
            <h2 class="mt-4 font-semibold">Inbox tidak memiliki hasil</h2>
            <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                Belum ada route yang cocok dengan pencarian atau rentang tanggal
                ini.
            </p>
            <Button
                type="button"
                variant="outline"
                class="mt-5 min-h-11"
                @click="$emit('reset')"
            >
                Tampilkan seluruh inbox
            </Button>
        </div>
        <template v-else>
            <ExecutiveInboxTable :routes="routes" />
            <ExecutiveInboxCards :routes="routes" />
            <slot name="pagination" />
        </template>
    </section>
</template>

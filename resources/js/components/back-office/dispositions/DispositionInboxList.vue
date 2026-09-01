<script setup lang="ts">
import { Inbox } from '@lucide/vue';
import DispositionInboxCards from '@/components/back-office/dispositions/DispositionInboxCards.vue';
import DispositionInboxTable from '@/components/back-office/dispositions/DispositionInboxTable.vue';
import { Button } from '@/components/ui/button';
import type { DispositionInboxItem } from '@/types';

defineProps<{ dispositions: DispositionInboxItem[] }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <div
            v-if="dispositions.length === 0"
            class="flex min-h-72 flex-col items-center justify-center px-5 py-12 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            >
                <Inbox class="size-5" aria-hidden="true" />
            </span>
            <h2 class="mt-4 font-semibold">Inbox tidak memiliki hasil</h2>
            <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                Belum ada disposisi yang cocok dengan pencarian, status, atau
                rentang tanggal ini.
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
            <DispositionInboxTable :dispositions="dispositions" />
            <DispositionInboxCards :dispositions="dispositions" />
            <slot name="pagination" />
        </template>
    </section>
</template>

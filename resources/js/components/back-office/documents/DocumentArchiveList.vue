<script setup lang="ts">
import DocumentArchiveCards from '@/components/back-office/documents/DocumentArchiveCards.vue';
import DocumentArchiveEmptyState from '@/components/back-office/documents/DocumentArchiveEmptyState.vue';
import DocumentArchiveTable from '@/components/back-office/documents/DocumentArchiveTable.vue';
import type { DocumentArchiveItem } from '@/types';

defineProps<{ documents: DocumentArchiveItem[] }>();
defineEmits<{ reset: [] }>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <DocumentArchiveEmptyState
            v-if="documents.length === 0"
            @reset="$emit('reset')"
        />
        <template v-else>
            <DocumentArchiveTable :documents="documents" />
            <DocumentArchiveCards :documents="documents" />
            <slot name="pagination" />
        </template>
    </section>
</template>

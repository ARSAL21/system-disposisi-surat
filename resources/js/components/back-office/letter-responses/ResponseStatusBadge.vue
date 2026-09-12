<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { LetterResponseStatus } from '@/types';

const props = defineProps<{
    status: LetterResponseStatus | 'OPEN' | 'FINALIZED';
}>();

const statusCopy: Record<string, string> = {
    PENDING: 'Menunggu',
    IN_PROGRESS: 'Diproses',
    COMPLETED: 'Selesai',
    READY: 'Siap ditinjau',
    REVISION_REQUIRED: 'Perlu revisi',
    AUTHORIZED: 'Mandat dibuat',
    NUMBER_ASSIGNED: 'Sudah bernomor',
    SIGNED_DOCUMENT_UPLOADED: 'Menunggu verifikasi',
    ADMIN_VERIFIED: 'Siap dikirim',
    DELIVERED: 'Sudah dikirim',
    WITHDRAWN: 'Ditarik',
    FULFILLED: 'Balasan terpenuhi',
    OPEN: 'Terbuka',
    FINALIZED: 'Terpenuhi',
};

const variant = computed(() =>
    props.status === 'IN_PROGRESS' || props.status === 'OPEN'
        ? ('secondary' as const)
        : ('outline' as const),
);

const toneClass = computed(() => {
    if (
        [
            'COMPLETED',
            'AUTHORIZED',
            'FINALIZED',
            'FULFILLED',
            'DELIVERED',
            'ADMIN_VERIFIED',
        ].includes(props.status)
    ) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300';
    }

    if (props.status === 'REVISION_REQUIRED') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300';
    }

    return '';
});
</script>

<template>
    <Badge
        :variant="variant"
        :class="['gap-1.5 rounded-full px-2.5', toneClass]"
    >
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
        {{ statusCopy[status] ?? status }}
    </Badge>
</template>

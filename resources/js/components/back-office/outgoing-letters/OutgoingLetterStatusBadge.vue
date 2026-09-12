<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { OutgoingLetterStatus } from '@/types';

const props = defineProps<{ status: OutgoingLetterStatus }>();

const copy: Record<OutgoingLetterStatus, string> = {
    AUTHORIZED: 'Menunggu nomor',
    NUMBER_ASSIGNED: 'Menunggu tanda tangan',
    SIGNED_DOCUMENT_UPLOADED: 'Menunggu verifikasi',
    ADMIN_VERIFIED: 'Siap dikirim',
    DELIVERED: 'Sudah dikirim',
    WITHDRAWN: 'Ditarik',
    SEKDA_REVIEW: 'Menunggu pengesahan Sekda',
    AWAITING_MANUAL_SIGNATURE: 'Menunggu tanda tangan Sekda',
    MANUAL_SCAN_REVIEW: 'Menunggu pemeriksaan scan',
    READY_FOR_DELIVERY: 'Siap dikirim',
    REVISION_REQUIRED: 'Perlu diperbaiki',
};

const tone = computed(() => {
    if (props.status === 'DELIVERED' || props.status === 'READY_FOR_DELIVERY') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300';
    }

    if (props.status === 'WITHDRAWN') {
        return 'border-slate-300 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300';
    }

    if (
        props.status === 'SIGNED_DOCUMENT_UPLOADED' ||
        props.status === 'MANUAL_SCAN_REVIEW'
    ) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300';
    }

    if (props.status === 'ADMIN_VERIFIED' || props.status === 'SEKDA_REVIEW') {
        return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300';
    }

    return 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-300';
});
</script>

<template>
    <Badge variant="outline" :class="['gap-1.5 rounded-full px-2.5', tone]">
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
        {{ copy[status] }}
    </Badge>
</template>

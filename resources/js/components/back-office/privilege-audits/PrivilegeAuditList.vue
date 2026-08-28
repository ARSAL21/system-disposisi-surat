<script setup lang="ts">
import PrivilegeAuditCards from '@/components/back-office/privilege-audits/PrivilegeAuditCards.vue';
import PrivilegeAuditEmptyState from '@/components/back-office/privilege-audits/PrivilegeAuditEmptyState.vue';
import PrivilegeAuditTable from '@/components/back-office/privilege-audits/PrivilegeAuditTable.vue';
import { Card } from '@/components/ui/card';
import type { PrivilegeAuditRecord } from '@/types';

defineProps<{
    audits: PrivilegeAuditRecord[];
    filtered: boolean;
}>();
defineEmits<{
    detail: [audit: PrivilegeAuditRecord];
    reset: [];
}>();
</script>

<template>
    <Card class="overflow-hidden py-0 shadow-sm" aria-live="polite">
        <template v-if="audits.length">
            <PrivilegeAuditTable
                :audits="audits"
                @detail="$emit('detail', $event)"
            />
            <PrivilegeAuditCards
                :audits="audits"
                @detail="$emit('detail', $event)"
            />
        </template>
        <PrivilegeAuditEmptyState
            v-else
            :filtered="filtered"
            @reset="$emit('reset')"
        />
        <slot name="pagination" />
    </Card>
</template>

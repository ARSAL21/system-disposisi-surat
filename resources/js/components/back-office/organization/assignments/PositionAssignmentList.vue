<script setup lang="ts">
import { BriefcaseBusiness } from '@lucide/vue';
import PositionAssignmentCards from '@/components/back-office/organization/assignments/PositionAssignmentCards.vue';
import PositionAssignmentTable from '@/components/back-office/organization/assignments/PositionAssignmentTable.vue';
import type { OrganizationPosition, Paginated } from '@/types';

defineProps<{
    positions: Paginated<OrganizationPosition>;
    canMutate: boolean;
}>();
const emit = defineEmits<{
    manage: [position: OrganizationPosition];
    history: [position: OrganizationPosition];
}>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <header class="border-b p-4">
            <h2 class="font-semibold">Keterisian jabatan</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Satu jabatan hanya boleh memiliki satu pejabat aktif pada satu
                waktu.
            </p>
        </header>
        <template v-if="positions.data.length">
            <PositionAssignmentTable
                :positions="positions.data"
                :can-mutate="canMutate"
                @manage="emit('manage', $event)"
                @history="emit('history', $event)"
            />
            <PositionAssignmentCards
                :positions="positions.data"
                :can-mutate="canMutate"
                @manage="emit('manage', $event)"
                @history="emit('history', $event)"
            />
        </template>
        <div v-else class="grid min-h-60 place-items-center p-8 text-center">
            <div>
                <BriefcaseBusiness
                    class="mx-auto size-8 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="mt-3 font-medium">Tidak ada jabatan yang cocok</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Sesuaikan filter untuk melihat data lain.
                </p>
            </div>
        </div>
        <slot name="pagination" />
    </section>
</template>

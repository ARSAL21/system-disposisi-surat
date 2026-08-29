<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AssignmentFilterBar from '@/components/back-office/organization/assignments/AssignmentFilterBar.vue';
import AssignmentHistoryPanel from '@/components/back-office/organization/assignments/AssignmentHistoryPanel.vue';
import EndAssignmentDialog from '@/components/back-office/organization/assignments/EndAssignmentDialog.vue';
import PositionAssignmentDialog from '@/components/back-office/organization/assignments/PositionAssignmentDialog.vue';
import PositionAssignmentList from '@/components/back-office/organization/assignments/PositionAssignmentList.vue';
import OrganizationMutationSecurityBanner from '@/components/back-office/organization/OrganizationMutationSecurityBanner.vue';
import OrganizationPageHeader from '@/components/back-office/organization/OrganizationPageHeader.vue';
import OrganizationPagination from '@/components/back-office/organization/OrganizationPagination.vue';
import OrganizationSummaryCards from '@/components/back-office/organization/OrganizationSummaryCards.vue';
import { usePositionAssignmentWorkspace } from '@/composables/usePositionAssignmentWorkspace';
import type { PositionAssignmentPageProps } from '@/types';

const props = defineProps<PositionAssignmentPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Penugasan Jabatan',
                href: '/back-office/organization/assignments',
            },
        ],
    },
});
const {
    activeFilters,
    processing,
    assignmentDialogOpen,
    endDialogOpen,
    managedPosition,
    endingAssignment,
    summaryItems,
    visit,
    apply,
    manage,
    requestEnd,
} = usePositionAssignmentWorkspace(props);
</script>

<template>
    <Head title="Penugasan Jabatan" />
    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <OrganizationPageHeader
            eyebrow="Position assignment"
            title="Penugasan pejabat"
            description="Kelola siapa yang menduduki jabatan, pertahankan histori, dan pastikan satu jabatan hanya memiliki satu pejabat aktif."
            :secondary-href="routes.structure"
            secondary-label="Lihat struktur"
        />
        <OrganizationSummaryCards :items="summaryItems" />
        <OrganizationMutationSecurityBanner
            :security="mutationSecurity"
            subject="penugasan jabatan"
        />
        <AssignmentFilterBar
            :filters="activeFilters"
            :levels="levels"
            :units="units"
            :processing="processing"
            @apply="apply"
            @reset="
                visit({
                    search: '',
                    status: 'all',
                    position_level_id: null,
                    organizational_unit_id: null,
                    selected_position: null,
                })
            "
        />
        <PositionAssignmentList
            :positions="positions"
            :can-mutate="mutationSecurity.can_mutate"
            @manage="manage"
            @history="visit({ selected_position: $event.id })"
        >
            <template #pagination
                ><OrganizationPagination
                    :pagination="positions"
                    :processing="processing"
                    @page="visit({}, $event)"
            /></template>
        </PositionAssignmentList>
        <AssignmentHistoryPanel
            v-if="selectedPosition && history"
            :position="selectedPosition"
            :history="history"
            @close="visit({ selected_position: null })"
            @page="visit({}, 1, $event)"
        />
        <PositionAssignmentDialog
            v-model:open="assignmentDialogOpen"
            :position="managedPosition"
            :users="users"
            @end="requestEnd"
        />
        <EndAssignmentDialog
            v-model:open="endDialogOpen"
            :assignment="endingAssignment"
        />
    </main>
</template>

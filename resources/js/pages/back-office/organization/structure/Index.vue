<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import OrganizationalUnitDialog from '@/components/back-office/organization/OrganizationalUnitDialog.vue';
import OrganizationalUnitList from '@/components/back-office/organization/OrganizationalUnitList.vue';
import OrganizationFilterBar from '@/components/back-office/organization/OrganizationFilterBar.vue';
import OrganizationMutationSecurityBanner from '@/components/back-office/organization/OrganizationMutationSecurityBanner.vue';
import OrganizationPageHeader from '@/components/back-office/organization/OrganizationPageHeader.vue';
import OrganizationPagination from '@/components/back-office/organization/OrganizationPagination.vue';
import OrganizationStatusDialog from '@/components/back-office/organization/OrganizationStatusDialog.vue';
import OrganizationStructureTabs from '@/components/back-office/organization/OrganizationStructureTabs.vue';
import OrganizationSummaryCards from '@/components/back-office/organization/OrganizationSummaryCards.vue';
import PositionDialog from '@/components/back-office/organization/PositionDialog.vue';
import PositionLevelGrid from '@/components/back-office/organization/PositionLevelGrid.vue';
import PositionList from '@/components/back-office/organization/PositionList.vue';
import { useOrganizationStructureWorkspace } from '@/composables/useOrganizationStructureWorkspace';
import type { OrganizationStructurePageProps } from '@/types';

const props = defineProps<OrganizationStructurePageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Struktur Organisasi',
                href: '/back-office/organization/structure',
            },
        ],
    },
});
const {
    activeFilters,
    processing,
    unitDialogOpen,
    positionDialogOpen,
    statusDialogOpen,
    selectedUnit,
    selectedPosition,
    statusResource,
    summaryItems,
    visit,
    apply,
    openUnit,
    openPosition,
    openStatus,
} = useOrganizationStructureWorkspace(props);
</script>

<template>
    <Head title="Struktur Organisasi" />
    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <OrganizationPageHeader
            eyebrow="Internal identity"
            title="Struktur organisasi"
            description="Kelola unit dan jabatan konkret tanpa mengubah empat level workflow yang dilindungi sistem."
            :secondary-href="routes.assignments"
            secondary-label="Kelola pejabat"
        />
        <OrganizationSummaryCards :items="summaryItems" />
        <OrganizationMutationSecurityBanner
            :security="mutationSecurity"
            subject="struktur organisasi"
        />
        <OrganizationStructureTabs
            :active="activeFilters.section"
            @change="
                visit({
                    section: $event,
                    search: '',
                    status: 'all',
                    position_level_id: null,
                    organizational_unit_id: null,
                })
            "
        />
        <OrganizationFilterBar
            v-if="activeFilters.section !== 'levels' && activeFilters.section !== 'chart'"
            :filters="activeFilters"
            :levels="levels"
            :units="unitOptions"
            :processing="processing"
            @apply="apply"
            @reset="
                visit({
                    search: '',
                    status: 'all',
                    position_level_id: null,
                    organizational_unit_id: null,
                })
            "
        />
        <PositionLevelGrid
            v-if="activeFilters.section === 'levels'"
            :levels="levels"
        />
        <OrganizationalUnitList
            v-else-if="units"
            :units="units"
            :tree="tree"
            :assignments-route="routes.assignments"
            :can-mutate="mutationSecurity.can_mutate"
            @create="openUnit(null)"
            @create-position="openPosition(null)"
            @edit="openUnit"
            @status="openStatus"
        >
            <template #pagination
                ><OrganizationPagination
                    :pagination="units"
                    :processing="processing"
                    @page="visit({}, $event)"
            /></template>
        </OrganizationalUnitList>
        <PositionList
            v-else-if="positions"
            :positions="positions"
            :can-mutate="mutationSecurity.can_mutate"
            @create="openPosition(null)"
            @edit="openPosition"
            @status="openStatus"
        >
            <template #pagination
                ><OrganizationPagination
                    :pagination="positions"
                    :processing="processing"
                    @page="visit({}, $event)"
            /></template>
        </PositionList>
        <OrganizationalUnitDialog
            v-model:open="unitDialogOpen"
            :unit="selectedUnit"
            :options="unitOptions"
            :store-url="routes.store_unit"
        />
        <PositionDialog
            v-model:open="positionDialogOpen"
            :position="selectedPosition"
            :levels="levels"
            :units="unitOptions"
            :store-url="routes.store_position"
        />
        <OrganizationStatusDialog
            v-if="statusResource"
            v-model:open="statusDialogOpen"
            :name="statusResource.name"
            :is-active="statusResource.is_active"
            :url="statusResource.links.status"
        />
    </main>
</template>

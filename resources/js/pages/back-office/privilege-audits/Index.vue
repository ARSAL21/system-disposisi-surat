<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PrivilegeAuditDetailDialog from '@/components/back-office/privilege-audits/PrivilegeAuditDetailDialog.vue';
import PrivilegeAuditFilterPanel from '@/components/back-office/privilege-audits/PrivilegeAuditFilterPanel.vue';
import PrivilegeAuditHeader from '@/components/back-office/privilege-audits/PrivilegeAuditHeader.vue';
import PrivilegeAuditList from '@/components/back-office/privilege-audits/PrivilegeAuditList.vue';
import PrivilegeAuditPagination from '@/components/back-office/privilege-audits/PrivilegeAuditPagination.vue';
import { index } from '@/routes/back-office/privilege-audits';
import type {
    PaginatedPrivilegeAudits,
    PrivilegeAuditFilterOptions,
    PrivilegeAuditFilters,
    PrivilegeAuditRecord,
    PrivilegeAuditRoutes,
    PrivilegeAuditSummary,
} from '@/types';

const props = defineProps<{
    audits: PaginatedPrivilegeAudits;
    filters: PrivilegeAuditFilters;
    filterOptions: PrivilegeAuditFilterOptions;
    summary: PrivilegeAuditSummary;
    routes: PrivilegeAuditRoutes;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Audit Perubahan Privilege',
                href: index(),
            },
        ],
    },
});

const activeFilters = ref<PrivilegeAuditFilters>({
    ...props.filters,
});
const processing = ref(false);
const selectedAudit = ref<PrivilegeAuditRecord | null>(null);
const detailOpen = ref(false);
const filtered = computed(() =>
    Object.values(activeFilters.value).some(Boolean),
);

watch(
    () => props.filters,
    (filters) => {
        activeFilters.value = { ...filters };
    },
);

function query(
    filters: PrivilegeAuditFilters,
    page = 1,
): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries({ ...filters, page }).filter(
            ([key, value]) =>
                Boolean(value) && !(key === 'page' && value === 1),
        ),
    );
}

function visit(filters: PrivilegeAuditFilters, page = 1): void {
    router.get(props.routes.index, query(filters, page), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
    });
}

function resetFilters(): void {
    visit({
        action: '',
        source: '',
        actor: '',
        target_type: '',
        target: '',
        date_from: '',
        date_to: '',
    });
}

function showDetail(audit: PrivilegeAuditRecord): void {
    selectedAudit.value = audit;
    detailOpen.value = true;
}
</script>

<template>
    <Head title="Audit Perubahan Privilege" />

    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <PrivilegeAuditHeader :summary="summary" />

        <PrivilegeAuditFilterPanel
            :filters="activeFilters"
            :options="filterOptions"
            :processing="processing"
            @apply="visit($event)"
            @reset="resetFilters"
        />

        <PrivilegeAuditList
            :audits="audits.data"
            :filtered="filtered"
            @detail="showDetail"
            @reset="resetFilters"
        >
            <template #pagination>
                <PrivilegeAuditPagination
                    :pagination="audits"
                    :processing="processing"
                    @page="visit(activeFilters, $event)"
                />
            </template>
        </PrivilegeAuditList>

        <PrivilegeAuditDetailDialog
            v-model:open="detailOpen"
            :audit="selectedAudit"
        />
    </main>
</template>

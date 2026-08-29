import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type {
    OrganizationalUnit,
    OrganizationPosition,
    OrganizationStructureFilters,
    OrganizationStructurePageProps,
} from '@/types';

export function useOrganizationStructureWorkspace(
    props: OrganizationStructurePageProps,
) {
    const activeFilters = ref({ ...props.filters });
    const processing = ref(false);
    const unitDialogOpen = ref(false);
    const positionDialogOpen = ref(false);
    const statusDialogOpen = ref(false);
    const selectedUnit = ref<OrganizationalUnit | null>(null);
    const selectedPosition = ref<OrganizationPosition | null>(null);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    watch(
        () => props.filters,
        (filters) => (activeFilters.value = { ...filters }),
    );

    const summaryItems = computed(() => [
        {
            label: 'Level terlindungi',
            value: props.summary.levels,
            kind: 'levels' as const,
        },
        {
            label: 'Unit aktif',
            value: props.summary.active_units,
            kind: 'units' as const,
        },
        {
            label: 'Jabatan aktif',
            value: props.summary.active_positions,
            kind: 'positions' as const,
        },
        {
            label: 'Jabatan terisi',
            value: props.summary.occupied_positions,
            kind: 'occupied' as const,
        },
    ]);
    const statusResource = computed(
        () => selectedUnit.value ?? selectedPosition.value,
    );

    function visit(
        changes: Partial<OrganizationStructureFilters> = {},
        page = 1,
    ): void {
        activeFilters.value = { ...activeFilters.value, ...changes };
        const query = Object.fromEntries(
            Object.entries({ ...activeFilters.value, page }).filter(
                ([, value]) =>
                    value !== null &&
                    value !== '' &&
                    value !== 'all' &&
                    value !== 1,
            ),
        );
        router.get(props.routes.index, query, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
        });
    }

    function apply(changes: Partial<OrganizationStructureFilters>): void {
        if ('search' in changes) {
            activeFilters.value = { ...activeFilters.value, ...changes };
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => visit(), 350);

            return;
        }

        visit(changes);
    }

    function openUnit(unit: OrganizationalUnit | null): void {
        selectedUnit.value = unit;
        unitDialogOpen.value = true;
    }

    function openPosition(position: OrganizationPosition | null): void {
        selectedPosition.value = position;
        positionDialogOpen.value = true;
    }

    function openStatus(resource: OrganizationalUnit | OrganizationPosition) {
        selectedUnit.value = 'children_count' in resource ? resource : null;
        selectedPosition.value = 'level' in resource ? resource : null;
        statusDialogOpen.value = true;
    }

    return {
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
    };
}

import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type {
    ActivePositionAssignment,
    OrganizationPosition,
    PositionAssignmentFilters,
    PositionAssignmentPageProps,
} from '@/types';

export function usePositionAssignmentWorkspace(
    props: PositionAssignmentPageProps,
) {
    const activeFilters = ref({ ...props.filters });
    const processing = ref(false);
    const assignmentDialogOpen = ref(false);
    const endDialogOpen = ref(false);
    const managedPosition = ref<OrganizationPosition | null>(null);
    const endingAssignment = ref<ActivePositionAssignment | null>(null);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    watch(
        () => props.filters,
        (filters) => (activeFilters.value = { ...filters }),
    );

    const summaryItems = computed(() => [
        {
            label: 'Total jabatan',
            value: props.summary.positions,
            kind: 'positions' as const,
        },
        {
            label: 'Terisi aktif',
            value: props.summary.occupied,
            kind: 'occupied' as const,
        },
        {
            label: 'Sedang lowong',
            value: props.summary.vacant,
            kind: 'units' as const,
        },
        {
            label: 'Jabatan nonaktif',
            value: props.summary.inactive,
            kind: 'levels' as const,
        },
    ]);

    function visit(
        changes: Partial<PositionAssignmentFilters> = {},
        page = 1,
        historyPage = 1,
    ): void {
        activeFilters.value = { ...activeFilters.value, ...changes };
        const query = Object.fromEntries(
            Object.entries({
                ...activeFilters.value,
                page,
                history_page: historyPage,
            }).filter(
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

    function apply(changes: Partial<PositionAssignmentFilters>): void {
        if ('search' in changes) {
            activeFilters.value = { ...activeFilters.value, ...changes };
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => visit(), 350);

            return;
        }

        visit(changes);
    }

    function manage(position: OrganizationPosition): void {
        managedPosition.value = position;
        assignmentDialogOpen.value = true;
    }

    function requestEnd(assignment: ActivePositionAssignment): void {
        assignmentDialogOpen.value = false;
        endingAssignment.value = assignment;
        endDialogOpen.value = true;
    }

    return {
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
    };
}

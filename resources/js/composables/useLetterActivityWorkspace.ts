import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    letterActivityPreviewDate,
    letterActivityPreviewOptions,
    letterActivityPreviewRecords,
    letterActivityPreviewTimezone,
} from '@/lib/letterActivityPreview';
import {
    filterLetterActivities,
    letterActivityDateLabel,
    paginateLetterActivities,
    sanitizeLetterActivity,
    summarizeLetterActivities,
} from '@/lib/letterActivityWorkspace';
import type {
    LetterActivityFilterOptions,
    LetterActivityFilters,
    LetterActivityRecord,
    LetterActivityRoutes,
    LetterActivitySummary,
    LetterActivityVisibility,
    PaginatedLetterActivities,
} from '@/types';

export type LetterActivityPageProps = {
    activities?: PaginatedLetterActivities;
    filters?: LetterActivityFilters;
    filterOptions?: LetterActivityFilterOptions;
    summary?: LetterActivitySummary;
    routes?: LetterActivityRoutes;
    visibility?: LetterActivityVisibility;
    timezone?: string;
    today?: string;
    preview?: boolean;
};

export function useLetterActivityWorkspace(props: LetterActivityPageProps) {
    const page = usePage();
    const today = props.today ?? letterActivityPreviewDate;
    const todayFilters: LetterActivityFilters = {
        action: '',
        source: '',
        actor: '',
        letter: '',
        date_from: today,
        date_to: today,
    };
    const activeFilters = ref<LetterActivityFilters>({
        ...todayFilters,
        ...props.filters,
    });
    const processing = ref(false);
    const selectedActivity = ref<LetterActivityRecord | null>(null);
    const detailOpen = ref(false);

    const previewMode = computed(() => props.preview ?? !props.activities);
    const timezone = computed(
        () => props.timezone ?? letterActivityPreviewTimezone,
    );
    const visibility = computed<LetterActivityVisibility>(
        () =>
            props.visibility ??
            (page.url.includes('/summary') ? 'summary' : 'details'),
    );
    const options = computed(
        () => props.filterOptions ?? letterActivityPreviewOptions,
    );
    const previewActivities = computed(() =>
        filterLetterActivities(
            letterActivityPreviewRecords,
            activeFilters.value,
            timezone.value,
        ),
    );
    const sourceActivities = computed(() =>
        previewMode.value
            ? previewActivities.value
            : (props.activities?.data ?? []),
    );
    const visibleActivities = computed(() =>
        sourceActivities.value.map((activity) =>
            sanitizeLetterActivity(activity, visibility.value),
        ),
    );
    const summary = computed(() =>
        !previewMode.value && props.summary
            ? props.summary
            : summarizeLetterActivities(sourceActivities.value),
    );
    const pagination = computed(() =>
        !previewMode.value && props.activities
            ? { ...props.activities, data: visibleActivities.value }
            : paginateLetterActivities(visibleActivities.value),
    );
    const filtered = computed(
        () =>
            activeFilters.value.action !== '' ||
            activeFilters.value.source !== '' ||
            activeFilters.value.actor !== '' ||
            activeFilters.value.letter !== '' ||
            activeFilters.value.date_from !== today ||
            activeFilters.value.date_to !== today,
    );
    const dateLabel = computed(() =>
        letterActivityDateLabel(activeFilters.value),
    );

    watch(
        () => props.filters,
        (filters) => {
            if (filters) {
                activeFilters.value = { ...filters };
            }
        },
    );

    function visit(filters: LetterActivityFilters, pageNumber = 1): void {
        activeFilters.value = { ...filters };

        if (previewMode.value) {
            return;
        }

        const query = Object.fromEntries(
            Object.entries({ ...filters, page: pageNumber }).filter(
                ([key, value]) => value && !(key === 'page' && value === 1),
            ),
        );

        router.get(props.routes?.index ?? page.url, query, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
        });
    }

    function resetFilters(): void {
        visit({ ...todayFilters });
    }

    function showDetail(activity: LetterActivityRecord): void {
        selectedActivity.value = activity;
        detailOpen.value = true;
    }

    return {
        activeFilters,
        dateLabel,
        detailOpen,
        filtered,
        options,
        pagination,
        previewMode,
        processing,
        resetFilters,
        selectedActivity,
        showDetail,
        summary,
        timezone,
        visibility,
        visit,
    };
}

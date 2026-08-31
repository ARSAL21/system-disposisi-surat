import { letterActivitySearchText } from '@/lib/letterActivityPresentation';
import type {
    LetterActivityFilters,
    LetterActivityRecord,
    LetterActivitySummary,
    LetterActivityVisibility,
    PaginatedLetterActivities,
} from '@/types';

export function letterActivityOfficeDate(
    value: string,
    timezone: string,
): string {
    const parts = new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        timeZone: timezone,
    }).formatToParts(new Date(value));
    const valueFor = (type: Intl.DateTimeFormatPartTypes) =>
        parts.find((part) => part.type === type)?.value ?? '';

    return `${valueFor('year')}-${valueFor('month')}-${valueFor('day')}`;
}

export function filterLetterActivities(
    activities: LetterActivityRecord[],
    filters: LetterActivityFilters,
    timezone: string,
): LetterActivityRecord[] {
    const letter = filters.letter.trim().toLocaleLowerCase('id-ID');

    return activities.filter((activity) => {
        const date = letterActivityOfficeDate(activity.occurred_at, timezone);

        return (
            (!filters.action || activity.action === filters.action) &&
            (!filters.source || activity.actor.kind === filters.source) &&
            (!filters.actor || String(activity.actor.id) === filters.actor) &&
            (!letter || letterActivitySearchText(activity).includes(letter)) &&
            (!filters.date_from || date >= filters.date_from) &&
            (!filters.date_to || date <= filters.date_to)
        );
    });
}

export function sanitizeLetterActivity(
    activity: LetterActivityRecord,
    visibility: LetterActivityVisibility,
): LetterActivityRecord {
    if (visibility === 'details') {
        return activity;
    }

    return {
        ...activity,
        actor: {
            kind: activity.actor.kind,
            id: null,
            name:
                activity.actor.kind === 'public'
                    ? 'Pengguna portal publik'
                    : 'Pengguna internal',
            position:
                activity.actor.kind === 'internal'
                    ? 'Identitas pelaksana dilindungi'
                    : null,
            unit: null,
        },
        target: null,
        before: null,
        after: null,
        document: null,
        request_id: null,
        ip_address: null,
        user_agent: null,
    };
}

export function summarizeLetterActivities(
    activities: LetterActivityRecord[],
): LetterActivitySummary {
    return {
        total: activities.length,
        received: activities.filter((item) =>
            ['SUBMISSION_SUBMITTED', 'SUBMISSION_RESUBMITTED'].includes(
                item.action,
            ),
        ).length,
        awaiting_approval: activities.filter(
            (item) => item.action === 'SUBMISSION_READY_FOR_APPROVAL',
        ).length,
        registered: activities.filter(
            (item) => item.action === 'LETTER_REGISTERED',
        ).length,
        needs_follow_up: activities.filter((item) =>
            [
                'SUBMISSION_REVISION_REQUESTED',
                'SUBMISSION_RETURNED_TO_STAFF',
                'SUBMISSION_REJECTED',
            ].includes(item.action),
        ).length,
    };
}

export function paginateLetterActivities(
    activities: LetterActivityRecord[],
): PaginatedLetterActivities {
    const total = activities.length;

    return {
        data: activities,
        meta: {
            current_page: 1,
            from: total ? 1 : null,
            last_page: 1,
            per_page: 20,
            to: total || null,
            total,
        },
    };
}

export function letterActivityDateLabel(
    filters: LetterActivityFilters,
): string {
    if (!filters.date_from && !filters.date_to) {
        return 'Semua tanggal';
    }

    const format = (value: string) =>
        new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
            new Date(`${value}T00:00:00`),
        );

    if (!filters.date_from) {
        return `Sampai ${format(filters.date_to)}`;
    }

    if (!filters.date_to) {
        return `Mulai ${format(filters.date_from)}`;
    }

    return filters.date_from === filters.date_to
        ? format(filters.date_from)
        : `${format(filters.date_from)} – ${format(filters.date_to)}`;
}

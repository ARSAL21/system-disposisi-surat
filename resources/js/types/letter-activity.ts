export type LetterActivityAction =
    | 'SUBMISSION_SUBMITTED'
    | 'SUBMISSION_RESUBMITTED'
    | 'SUBMISSION_REVISION_REQUESTED'
    | 'SUBMISSION_READY_FOR_APPROVAL'
    | 'SUBMISSION_RETURNED_TO_STAFF'
    | 'SUBMISSION_REJECTED'
    | 'LETTER_REGISTERED'
    | 'LETTER_ROUTED'
    | 'DISPOSITION_CREATED'
    | 'DOCUMENT_VERSION_CREATED';

export type LetterActivityVisibility = 'details' | 'summary';

export type LetterActivityValue = string | number | boolean | null;

export type LetterActivityActor = {
    kind: 'public' | 'internal' | 'system';
    id: number | null;
    name: string;
    position: string | null;
    unit: string | null;
};

export type LetterActivityTarget = {
    public_id: string | null;
    agenda_number: string | null;
    subject: string;
    sender: string;
    source: 'ONLINE' | 'MANUAL' | null;
};

export type LetterActivityDocument = {
    version_number: number;
    sha256: string;
};

export type LetterActivityRecord = {
    id: number;
    action: LetterActivityAction;
    actor: LetterActivityActor;
    target: LetterActivityTarget | null;
    before: Record<string, LetterActivityValue> | null;
    after: Record<string, LetterActivityValue> | null;
    document: LetterActivityDocument | null;
    request_id: string | null;
    ip_address: string | null;
    user_agent: string | null;
    occurred_at: string;
};

export type LetterActivityFilters = {
    action: LetterActivityAction | '';
    source: 'public' | 'internal' | '';
    actor: string;
    letter: string;
    date_from: string;
    date_to: string;
};

export type LetterActivityFilterOption<T extends string> = {
    value: T;
    label: string;
};

export type LetterActivityFilterOptions = {
    actions: LetterActivityFilterOption<LetterActivityAction>[];
    sources: LetterActivityFilterOption<'public' | 'internal'>[];
    actors: LetterActivityFilterOption<string>[];
};

export type LetterActivitySummary = {
    total: number;
    received: number;
    awaiting_approval: number;
    registered: number;
    needs_follow_up: number;
};

export type PaginatedLetterActivities = {
    data: LetterActivityRecord[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type LetterActivityRoutes = {
    index: string;
};

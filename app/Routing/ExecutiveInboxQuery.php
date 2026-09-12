<?php

namespace App\Routing;

use App\Enums\LetterRouteStatus;
use App\Models\DispositionRecipient;
use App\Models\LetterRoute;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\LetterRoutingPositionAssignmentResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ExecutiveInboxQuery
{
    public function __construct(
        private readonly LetterRoutingPositionAssignmentResolver $positionAssignmentResolver,
    ) {}

    /**
     * @param  array{search: string, progress: string, date_from: string, date_to: string}  $filters
     * @return LengthAwarePaginator<int, object>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $direct = $this->directEntries($user, $filters);
        $throughMayor = $this->sekdaEntriesFromMayor($user, $filters);
        $entries = DB::query()->fromSub($direct->unionAll($throughMayor), 'executive_inbox_entries')
            ->orderByDesc('received_in_inbox_at')
            ->orderByDesc('entry_id');

        /** @var LengthAwarePaginator<int, object> $paginator */
        $paginator = $entries->paginate(20)->withQueryString();

        return $paginator;
    }

    /**
     * @param  LengthAwarePaginator<int, object>  $paginator
     * @return Collection<int, array{entry_type: string, entry_id: int, model: LetterRoute|DispositionRecipient}>
     */
    public function hydrate(LengthAwarePaginator $paginator): Collection
    {
        $rows = collect($paginator->items());
        $routeIds = $rows->where('entry_type', 'DIRECT_ROUTE')->pluck('entry_id')->map(static fn (mixed $id): int => (int) $id)->all();
        $recipientIds = $rows->where('entry_type', 'MAYOR_DISPOSITION')->pluck('entry_id')->map(static fn (mixed $id): int => (int) $id)->all();

        $routes = LetterRoute::query()->whereIn('id', $routeIds)->with($this->routeRelations())
            ->get()->keyBy(fn (LetterRoute $route): int => (int) $route->getKey());
        $recipients = DispositionRecipient::query()->whereIn('id', $recipientIds)->with($this->recipientRelations())
            ->get()->keyBy(fn (DispositionRecipient $recipient): int => (int) $recipient->getKey());

        return $rows->map(function (object $row) use ($routes, $recipients): array {
            $attributes = get_object_vars($row);
            $entryId = $this->entryId($attributes);
            $entryType = $this->entryType($attributes);
            $model = $entryType === 'DIRECT_ROUTE' ? $routes->get($entryId) : $recipients->get($entryId);

            if (! $model instanceof LetterRoute && ! $model instanceof DispositionRecipient) {
                throw new \LogicException('Executive inbox entry changed before it could be presented.');
            }

            return [
                'entry_type' => $entryType,
                'entry_id' => $entryId,
                'model' => $model,
            ];
        })->values();
    }

    /** @return array{pending: int, awaiting_forwarding: int, in_progress: int, completed: int, received_today: int} */
    public function summary(User $user): array
    {
        $filters = ['search' => '', 'progress' => '', 'date_from' => '', 'date_to' => ''];
        $entries = DB::query()->fromSub(
            $this->directEntries($user, $filters)->unionAll($this->sekdaEntriesFromMayor($user, $filters)),
            'executive_inbox_entries',
        );
        [$start, $end] = $this->officeDayUtcBounds();

        return [
            'pending' => (clone $entries)->where('phase', 'AWAITING_DECISION')->count(),
            'awaiting_forwarding' => (clone $entries)->where('phase', 'AWAITING_FORWARDING')->count(),
            'in_progress' => (clone $entries)->where('phase', 'IN_PROGRESS')->count(),
            'completed' => (clone $entries)->where('phase', 'COMPLETED')->count(),
            'received_today' => (clone $entries)->whereBetween('received_in_inbox_at', [$start, $end])->count(),
        ];
    }

    /**
     * @param  array{search: string, progress: string, date_from: string, date_to: string}  $filters
     */
    private function directEntries(User $user, array $filters): QueryBuilder
    {
        $positionIds = $this->positionAssignmentResolver->executivePositionIds($user);
        $phaseExpression = $this->directPhaseExpression();
        $query = DB::table('letter_routes as routes')
            ->join('incoming_letters as letters', 'letters.id', '=', 'routes.incoming_letter_id')
            ->selectRaw("'DIRECT_ROUTE' as entry_type, routes.id as entry_id, routes.incoming_letter_id, routes.routed_at as received_in_inbox_at, {$phaseExpression} as phase");

        if ($positionIds === []) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('routes.recipient_position_id', $positionIds)
            ->where(function (QueryBuilder $states): void {
                $states->where(function (QueryBuilder $pending): void {
                    $pending->where('routes.status', LetterRouteStatus::Pending->value)
                        ->whereNotExists(fn (QueryBuilder $disposition) => $disposition->selectRaw('1')->from('dispositions')
                            ->whereColumn('dispositions.source_route_id', 'routes.id'));
                })->orWhere(function (QueryBuilder $completed): void {
                    $completed->where('routes.status', LetterRouteStatus::Completed->value)
                        ->whereExists(fn (QueryBuilder $disposition) => $disposition->selectRaw('1')->from('dispositions')
                            ->whereColumn('dispositions.source_route_id', 'routes.id'));
                });
            });

        return $this->applyFilters($query, $filters, 'routes.routed_at', $phaseExpression);
    }

    /**
     * @param  array{search: string, progress: string, date_from: string, date_to: string}  $filters
     */
    private function sekdaEntriesFromMayor(User $user, array $filters): QueryBuilder
    {
        $sekdaPositionIds = $this->regionalSecretaryPositionIds($user);
        $phaseExpression = $this->mayorSekdaPhaseExpression();
        $query = DB::table('disposition_recipients as recipients')
            ->join('dispositions as dispositions', 'dispositions.id', '=', 'recipients.disposition_id')
            ->join('letter_routes as routes', 'routes.id', '=', 'dispositions.source_route_id')
            ->join('positions as route_positions', 'route_positions.id', '=', 'routes.recipient_position_id')
            ->join('position_levels as route_levels', 'route_levels.id', '=', 'route_positions.position_level_id')
            ->join('incoming_letters as letters', 'letters.id', '=', 'dispositions.incoming_letter_id')
            ->selectRaw("'MAYOR_DISPOSITION' as entry_type, recipients.id as entry_id, dispositions.incoming_letter_id, recipients.received_at as received_in_inbox_at, {$phaseExpression} as phase");

        if ($sekdaPositionIds === []) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('recipients.recipient_position_id', $sekdaPositionIds)
            ->where('route_positions.code', OrganizationCatalog::MAYOR_POSITION)
            ->where('route_positions.is_active', true)
            ->where('route_levels.code', OrganizationCatalog::MAYOR_LEVEL)
            ->where('route_levels.is_active', true)
            ->where(function (QueryBuilder $states): void {
                $states->where(function (QueryBuilder $pending): void {
                    $pending->where('recipients.status', 'PENDING')
                        ->whereNotExists(fn (QueryBuilder $child) => $child->selectRaw('1')->from('dispositions as child_dispositions')
                            ->whereColumn('child_dispositions.parent_recipient_id', 'recipients.id'));
                })->orWhere(function (QueryBuilder $completed): void {
                    $completed->where('recipients.status', 'COMPLETED')
                        ->whereExists(fn (QueryBuilder $child) => $child->selectRaw('1')->from('dispositions as child_dispositions')
                            ->whereColumn('child_dispositions.parent_recipient_id', 'recipients.id'));
                });
            });

        return $this->applyFilters($query, $filters, 'recipients.received_at', $phaseExpression);
    }

    /**
     * The direct inbox may contain either a Sekda route or a Wali Kota route.
     * A Wali Kota route is only ready for forwarding once its created
     * disposition has reached the single Sekda recipient; a Sekda route is
     * ready for forwarding when an Assistant still has no child disposition.
     *
     * This is deliberately expressed in SQL because the inbox summary and its
     * progress filter must never derive state from a paginated collection.
     *
     * @return literal-string
     */
    private function directPhaseExpression(): string
    {
        $pending = LetterRouteStatus::Pending->value;
        $completed = 'COMPLETED';
        $sekda = OrganizationCatalog::REGIONAL_SECRETARY_LEVEL;
        $assistant = OrganizationCatalog::ASSISTANT_LEVEL;

        return <<<SQL
CASE
    WHEN routes.status = '{$pending}' THEN 'AWAITING_DECISION'
    WHEN letters.status = '{$completed}' THEN 'COMPLETED'
    WHEN EXISTS (
        SELECT 1
        FROM dispositions AS root_dispositions
        INNER JOIN disposition_recipients AS root_recipients
            ON root_recipients.disposition_id = root_dispositions.id
        INNER JOIN positions AS root_positions
            ON root_positions.id = root_recipients.recipient_position_id
        INNER JOIN position_levels AS root_levels
            ON root_levels.id = root_positions.position_level_id
        WHERE root_dispositions.source_route_id = routes.id
          AND (
              (
                  root_levels.code IN ('{$sekda}', '{$assistant}')
                  AND NOT EXISTS (
                      SELECT 1
                      FROM dispositions AS root_children
                      WHERE root_children.parent_recipient_id = root_recipients.id
                  )
              )
              OR (
                  root_levels.code = '{$sekda}'
                  AND EXISTS (
                      SELECT 1
                      FROM dispositions AS sekda_children
                      INNER JOIN disposition_recipients AS assistant_recipients
                          ON assistant_recipients.disposition_id = sekda_children.id
                      INNER JOIN positions AS assistant_positions
                          ON assistant_positions.id = assistant_recipients.recipient_position_id
                      INNER JOIN position_levels AS assistant_levels
                          ON assistant_levels.id = assistant_positions.position_level_id
                      WHERE sekda_children.parent_recipient_id = root_recipients.id
                        AND assistant_levels.code = '{$assistant}'
                        AND NOT EXISTS (
                            SELECT 1
                            FROM dispositions AS assistant_children
                            WHERE assistant_children.parent_recipient_id = assistant_recipients.id
                        )
                  )
              )
          )
    ) THEN 'AWAITING_FORWARDING'
    ELSE 'IN_PROGRESS'
END
SQL;
    }

    /**
     * Rows in this query are the Sekda recipient created by a Wali Kota
     * disposition. Their state is separate from the Wali Kota's direct route,
     * so the ordering below mirrors the two steps Sekda can still take.
     *
     * @return literal-string
     */
    private function mayorSekdaPhaseExpression(): string
    {
        $pending = LetterRouteStatus::Pending->value;
        $completed = 'COMPLETED';
        $assistant = OrganizationCatalog::ASSISTANT_LEVEL;

        return <<<SQL
CASE
    WHEN recipients.status = '{$pending}' THEN 'AWAITING_DECISION'
    WHEN letters.status = '{$completed}' THEN 'COMPLETED'
    WHEN NOT EXISTS (
        SELECT 1
        FROM dispositions AS sekda_children
        WHERE sekda_children.parent_recipient_id = recipients.id
    ) THEN 'AWAITING_FORWARDING'
    WHEN EXISTS (
        SELECT 1
        FROM dispositions AS sekda_children
        INNER JOIN disposition_recipients AS assistant_recipients
            ON assistant_recipients.disposition_id = sekda_children.id
        INNER JOIN positions AS assistant_positions
            ON assistant_positions.id = assistant_recipients.recipient_position_id
        INNER JOIN position_levels AS assistant_levels
            ON assistant_levels.id = assistant_positions.position_level_id
        WHERE sekda_children.parent_recipient_id = recipients.id
          AND assistant_levels.code = '{$assistant}'
          AND NOT EXISTS (
              SELECT 1
              FROM dispositions AS assistant_children
              WHERE assistant_children.parent_recipient_id = assistant_recipients.id
          )
    ) THEN 'AWAITING_FORWARDING'
    ELSE 'IN_PROGRESS'
END
SQL;
    }

    /** @return list<int> */
    private function regionalSecretaryPositionIds(User $user): array
    {
        return $this->positionAssignmentResolver->regionalSecretaryPositionIds($user);
    }

    /**
     * @param  array{search: string, progress: string, date_from: string, date_to: string}  $filters
     * @param  literal-string  $phaseExpression
     */
    private function applyFilters(QueryBuilder $query, array $filters, string $receivedAtColumn, string $phaseExpression): QueryBuilder
    {
        if ($filters['search'] !== '') {
            $pattern = '%'.$filters['search'].'%';
            $query->leftJoin('sender_organizations as senders', 'senders.id', '=', 'letters.sender_organization_id')
                ->where(function (QueryBuilder $search) use ($pattern): void {
                    $search->where('letters.agenda_number', 'like', $pattern)
                        ->orWhere('letters.subject', 'like', $pattern)
                        ->orWhere('letters.external_letter_number', 'like', $pattern)
                        ->orWhere('senders.name', 'like', $pattern);
                });
        }

        if ($filters['progress'] !== '') {
            $query->where(DB::raw($phaseExpression), '=', $filters['progress']);
        }

        $timezone = (string) config('letter-activity.timezone');
        if ($filters['date_from'] !== '') {
            $query->where($receivedAtColumn, '>=', CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_from'], $timezone)->utc());
        }
        if ($filters['date_to'] !== '') {
            $query->where($receivedAtColumn, '<=', CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_to'], $timezone)->endOfDay()->utc());
        }

        return $query;
    }

    /** @param array<string, mixed> $attributes */
    private function entryId(array $attributes): int
    {
        $entryId = $attributes['entry_id'] ?? null;

        if (! is_int($entryId) && ! is_string($entryId) && ! is_float($entryId)) {
            throw new \LogicException('Executive inbox entry has an invalid identifier.');
        }

        return (int) $entryId;
    }

    /** @param array<string, mixed> $attributes */
    private function entryType(array $attributes): string
    {
        $entryType = $attributes['entry_type'] ?? null;

        if (! in_array($entryType, ['DIRECT_ROUTE', 'MAYOR_DISPOSITION'], true)) {
            throw new \LogicException('Executive inbox entry has an invalid type.');
        }

        return $entryType;
    }

    /** @return array<int, string> */
    private function routeRelations(): array
    {
        return [
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'routedBy:id,name',
            'routedByPositionAssignment.position.organizationalUnit:id,name',
            'incomingLetter.senderOrganization:id,name',
            'incomingLetter.currentDocument.sourceSubmissionDocument.submission',
            'incomingLetter.currentDocument.replacesDocument',
            'incomingLetter.currentRoute.recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'incomingLetter.currentRoute.routedBy:id,name',
            'incomingLetter.currentRoute.routedByPositionAssignment.position.organizationalUnit:id,name',
            'disposition.recipients.recipientPosition.positionLevel:id,code',
            'disposition.recipients.childDispositions.recipients:id,disposition_id,recipient_position_id,status',
            'disposition.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
            'disposition.recipients.childDispositions.recipients.childDispositions.recipients:id,disposition_id,recipient_position_id,status',
            'disposition.recipients.childDispositions.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
        ];
    }

    /** @return array<int, string> */
    private function recipientRelations(): array
    {
        return [
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'disposition.instructionLabels:id,code,name,description,sort_order,is_active',
            'disposition.createdBy:id,name',
            'disposition.createdByPositionAssignment.position.organizationalUnit:id,name',
            'disposition.incomingLetter.senderOrganization:id,name',
            'disposition.incomingLetter.currentDocument.sourceSubmissionDocument.submission',
            'disposition.incomingLetter.currentDocument.replacesDocument',
            'disposition.incomingLetter.currentRoute.recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'disposition.incomingLetter.currentRoute.routedBy:id,name',
            'disposition.incomingLetter.currentRoute.routedByPositionAssignment.position.organizationalUnit:id,name',
            'childDispositions.recipients.recipientPosition.positionLevel:id,code',
            'childDispositions.recipients.childDispositions.recipients:id,disposition_id,recipient_position_id,status',
            'childDispositions.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
        ];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function officeDayUtcBounds(): array
    {
        $now = CarbonImmutable::now((string) config('letter-activity.timezone'));

        return [$now->startOfDay()->utc(), $now->endOfDay()->utc()];
    }
}

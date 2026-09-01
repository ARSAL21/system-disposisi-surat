<?php

namespace App\Routing;

use App\Enums\LetterRouteStatus;
use App\Models\LetterRoute;
use App\Models\User;
use App\Services\LetterRoutingPositionAssignmentResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class ExecutiveInboxQuery
{
    public function __construct(
        private readonly LetterRoutingPositionAssignmentResolver $positionAssignmentResolver,
        private readonly LetterRoutingQuery $routingQuery,
    ) {}

    /**
     * @param  array{search: string, date_from: string, date_to: string}  $filters
     * @return Builder<LetterRoute>
     */
    public function build(User $user, array $filters): Builder
    {
        $query = $this->authorized($user)->with([
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'routedBy:id,name',
            'routedByPositionAssignment.position.organizationalUnit:id,name',
            'incomingLetter' => fn ($letter) => $letter->with($this->routingQuery->relations()),
        ]);

        if ($filters['search'] !== '') {
            $pattern = '%'.$filters['search'].'%';

            $query->whereHas('incomingLetter', function (Builder $letter) use ($pattern): void {
                $letter
                    ->where('agenda_number', 'like', $pattern)
                    ->orWhere('subject', 'like', $pattern)
                    ->orWhere('external_letter_number', 'like', $pattern)
                    ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                        ->where('name', 'like', $pattern));
            });
        }

        $this->applyRoutedDateRange($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->orderByDesc('routed_at')
            ->orderByDesc('id');
    }

    /** @return array{pending: int, received_today: int} */
    public function summary(User $user): array
    {
        $scope = $this->authorized($user);
        [$dayStart, $dayEnd] = $this->officeDayUtcBounds();

        return [
            'pending' => (clone $scope)->count(),
            'received_today' => (clone $scope)
                ->whereBetween('routed_at', [$dayStart, $dayEnd])
                ->count(),
        ];
    }

    /** @return Builder<LetterRoute> */
    public function authorized(User $user): Builder
    {
        $query = LetterRoute::query()
            ->where('status', LetterRouteStatus::Pending->value);
        $positionIds = $this->positionAssignmentResolver->executivePositionIds($user);

        if ($positionIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('recipient_position_id', $positionIds);
    }

    /** @param Builder<LetterRoute> $query */
    private function applyRoutedDateRange(Builder $query, string $dateFrom, string $dateTo): void
    {
        $timezone = (string) config('letter-activity.timezone');

        if ($dateFrom !== '') {
            $query->where(
                'routed_at',
                '>=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->utc(),
            );
        }

        if ($dateTo !== '') {
            $query->where(
                'routed_at',
                '<=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, $timezone)->endOfDay()->utc(),
            );
        }
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function officeDayUtcBounds(): array
    {
        $now = CarbonImmutable::now((string) config('letter-activity.timezone'));

        return [
            $now->startOfDay()->utc(),
            $now->endOfDay()->utc(),
        ];
    }
}

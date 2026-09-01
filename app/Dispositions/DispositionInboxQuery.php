<?php

namespace App\Dispositions;

use App\Enums\DispositionRecipientStatus;
use App\Models\DispositionRecipient;
use App\Models\User;
use App\Routing\LetterRoutingQuery;
use App\Services\DispositionPositionAssignmentResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class DispositionInboxQuery
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
        private readonly LetterRoutingQuery $routingQuery,
    ) {}

    /**
     * @param  array{search: string, status: string, date_from: string, date_to: string}  $filters
     * @return Builder<DispositionRecipient>
     */
    public function build(User $user, array $filters): Builder
    {
        $query = $this->authorized($user)->with($this->relations());

        if ($filters['search'] !== '') {
            $pattern = '%'.$filters['search'].'%';

            $query->whereHas('disposition.incomingLetter', function (Builder $letter) use ($pattern): void {
                $letter
                    ->where('agenda_number', 'like', $pattern)
                    ->orWhere('subject', 'like', $pattern)
                    ->orWhere('external_letter_number', 'like', $pattern)
                    ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                        ->where('name', 'like', $pattern));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $this->applyReceivedDateRange($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }

    /** @return array{pending: int, in_progress: int, received_today: int} */
    public function summary(User $user): array
    {
        $scope = $this->authorized($user);
        [$dayStart, $dayEnd] = $this->officeDayUtcBounds();

        return [
            'pending' => (clone $scope)
                ->where('status', DispositionRecipientStatus::Pending->value)
                ->count(),
            'in_progress' => (clone $scope)
                ->where('status', DispositionRecipientStatus::InProgress->value)
                ->count(),
            'received_today' => (clone $scope)
                ->whereBetween('received_at', [$dayStart, $dayEnd])
                ->count(),
        ];
    }

    /** @return Builder<DispositionRecipient> */
    public function authorized(User $user): Builder
    {
        $query = DispositionRecipient::query();
        $positionIds = $this->positionAssignmentResolver->inboxPositionIds($user);

        if ($positionIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('recipient_position_id', $positionIds);
    }

    /** @return array<int|string, mixed> */
    public function relations(): array
    {
        return [
            'recipientPosition.positionLevel:id,code',
            'recipientPosition.organizationalUnit:id,name',
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'disposition.instructionLabels:id,code,name,description,sort_order,is_active',
            'disposition.createdBy:id,name',
            'disposition.createdByPositionAssignment.position.organizationalUnit:id,name',
            'disposition.incomingLetter' => fn ($letter) => $letter->with($this->routingQuery->relations()),
        ];
    }

    /** @param Builder<DispositionRecipient> $query */
    private function applyReceivedDateRange(Builder $query, string $dateFrom, string $dateTo): void
    {
        $timezone = (string) config('letter-activity.timezone');

        if ($dateFrom !== '') {
            $query->where(
                'received_at',
                '>=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->utc(),
            );
        }

        if ($dateTo !== '') {
            $query->where(
                'received_at',
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

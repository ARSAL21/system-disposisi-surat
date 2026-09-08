<?php

namespace App\Routing;

use App\Enums\DispositionRecipientStatus;
use App\Enums\LetterRouteStatus;
use App\Models\LetterRoute;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\LetterRoutingPositionAssignmentResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ExecutiveInboxQuery
{
    public function __construct(
        private readonly LetterRoutingPositionAssignmentResolver $positionAssignmentResolver,
        private readonly LetterRoutingQuery $routingQuery,
    ) {}

    /**
     * @param  array{search: string, progress: string, date_from: string, date_to: string}  $filters
     * @return Builder<LetterRoute>
     */
    public function build(User $user, array $filters): Builder
    {
        $query = $this->authorized($user)->with([
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'routedBy:id,name',
            'routedByPositionAssignment.position.organizationalUnit:id,name',
            'incomingLetter' => fn ($letter) => $letter->with($this->routingQuery->relations()),
            'disposition.recipients.recipientPosition.positionLevel:id,code',
            'disposition.recipients.childDispositions.recipients:id,disposition_id,recipient_position_id,status',
            'disposition.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
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

        $this->applyProgressFilter($query, $filters['progress']);

        $this->applyRoutedDateRange($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->orderByDesc('routed_at')
            ->orderByDesc('id');
    }

    /** @return array{pending: int, awaiting_forwarding: int, in_progress: int, completed: int, received_today: int} */
    public function summary(User $user): array
    {
        $scope = $this->authorized($user);
        [$dayStart, $dayEnd] = $this->officeDayUtcBounds();

        $awaitingDecision = clone $scope;
        $awaitingForwarding = clone $scope;
        $inProgress = clone $scope;
        $completed = clone $scope;
        $this->applyProgressFilter($awaitingDecision, 'AWAITING_DECISION');
        $this->applyProgressFilter($awaitingForwarding, 'AWAITING_FORWARDING');
        $this->applyProgressFilter($inProgress, 'IN_PROGRESS');
        $this->applyProgressFilter($completed, 'COMPLETED');

        return [
            'pending' => $awaitingDecision->count(),
            'awaiting_forwarding' => $awaitingForwarding->count(),
            'in_progress' => $inProgress->count(),
            'completed' => $completed->count(),
            'received_today' => (clone $scope)
                ->whereBetween('routed_at', [$dayStart, $dayEnd])
                ->count(),
        ];
    }

    /** @return Builder<LetterRoute> */
    public function authorized(User $user): Builder
    {
        $query = LetterRoute::query()
            ->where(function (Builder $scope): void {
                $scope
                    ->where(function (Builder $pending): void {
                        $pending
                            ->where('status', LetterRouteStatus::Pending->value)
                            ->whereDoesntHave('disposition');
                    })
                    ->orWhere(function (Builder $completed): void {
                        $completed
                            ->where('status', LetterRouteStatus::Completed->value)
                            ->whereHas('disposition');
                    });
            });
        $positionIds = $this->positionAssignmentResolver->executivePositionIds($user);

        if ($positionIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('recipient_position_id', $positionIds);
    }

    /** @param Builder<LetterRoute> $query */
    private function applyProgressFilter(Builder $query, string $progress): void
    {
        if ($progress === '') {
            return;
        }

        if ($progress === 'AWAITING_DECISION') {
            $query
                ->where('status', LetterRouteStatus::Pending->value)
                ->whereDoesntHave('disposition');

            return;
        }

        $query->where('status', LetterRouteStatus::Completed->value);

        if ($progress === 'AWAITING_FORWARDING') {
            $query
                ->whereHas('disposition')
                ->whereHas(
                    'disposition.recipients',
                    fn (Builder $recipient): Builder => $this->unforwardedAssistantRecipient($recipient),
                );

            return;
        }

        $query->whereDoesntHave(
            'disposition.recipients',
            fn (Builder $recipient): Builder => $this->unforwardedAssistantRecipient($recipient),
        );
        $query->whereHas(
            'disposition.recipients.childDispositions.recipients',
            fn (Builder $recipient): Builder => $this->terminalRecipient($recipient),
        );

        if ($progress === 'IN_PROGRESS') {
            $query->whereHas(
                'disposition.recipients.childDispositions.recipients',
                fn (Builder $recipient): Builder => $this->terminalRecipient($recipient)
                    ->where('status', '!=', DispositionRecipientStatus::Completed->value),
            );

            return;
        }

        $query->whereDoesntHave(
            'disposition.recipients.childDispositions.recipients',
            fn (Builder $recipient): Builder => $this->terminalRecipient($recipient)
                ->where('status', '!=', DispositionRecipientStatus::Completed->value),
        );
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function terminalRecipient(Builder $query): Builder
    {
        return $query->whereHas('recipientPosition.positionLevel', fn (Builder $level): Builder => $level
            ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL));
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function unforwardedAssistantRecipient(Builder $query): Builder
    {
        return $query
            ->whereHas('recipientPosition.positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::ASSISTANT_LEVEL))
            ->whereDoesntHave(
                'childDispositions.recipients',
                fn (Builder $recipient): Builder => $this->terminalRecipient($recipient),
            );
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

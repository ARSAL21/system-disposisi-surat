<?php

namespace App\Routing;

use App\Enums\IncomingLetterStatus;
use App\Enums\LetterRouteStatus;
use App\Models\IncomingLetter;
use App\Models\LetterRoute;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class LetterRoutingQuery
{
    /**
     * @param  array{search: string, status: string}  $filters
     * @return Builder<IncomingLetter>
     */
    public function build(User $user, array $filters): Builder
    {
        $query = $this->authorized($user)->with($this->relations());

        if ($filters['search'] !== '') {
            $pattern = '%'.$filters['search'].'%';

            $query->where(function (Builder $search) use ($pattern): void {
                $search
                    ->where('agenda_number', 'like', $pattern)
                    ->orWhere('subject', 'like', $pattern)
                    ->orWhere('external_letter_number', 'like', $pattern)
                    ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                        ->where('name', 'like', $pattern))
                    ->orWhereHas('documents', fn (Builder $documents): Builder => $documents
                        ->where('original_filename', 'like', $pattern));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }

    /** @return array{awaiting_route: int, pending_executive: int, routed_today: int} */
    public function summary(User $user): array
    {
        $scope = $this->authorized($user);
        $letterIds = (clone $scope)->select('incoming_letters.id');
        [$dayStart, $dayEnd] = $this->officeDayUtcBounds();

        return [
            'awaiting_route' => (clone $scope)
                ->where('status', IncomingLetterStatus::Registered->value)
                ->count(),
            'pending_executive' => LetterRoute::query()
                ->whereIn('incoming_letter_id', clone $letterIds)
                ->where('status', LetterRouteStatus::Pending->value)
                ->count(),
            'routed_today' => LetterRoute::query()
                ->whereIn('incoming_letter_id', clone $letterIds)
                ->whereBetween('routed_at', [$dayStart, $dayEnd])
                ->count(),
        ];
    }

    /** @return Builder<IncomingLetter> */
    public function authorized(User $user): Builder
    {
        $query = IncomingLetter::query()->whereIn('status', [
            IncomingLetterStatus::Registered->value,
            IncomingLetterStatus::Routed->value,
        ]);

        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function (QueryBuilder $assignments) use ($user): void {
            $assignments
                ->selectRaw('1')
                ->from('position_assignments as routing_assignments')
                ->join('positions as routing_positions', 'routing_positions.id', '=', 'routing_assignments.position_id')
                ->join('position_levels as routing_levels', 'routing_levels.id', '=', 'routing_positions.position_level_id')
                ->join('organizational_units as routing_units', 'routing_units.id', '=', 'routing_positions.organizational_unit_id')
                ->where('routing_assignments.user_id', $user->getKey())
                ->where('routing_assignments.started_at', '<=', now())
                ->whereNull('routing_assignments.ended_at')
                ->where('routing_positions.is_active', true)
                ->where('routing_levels.is_active', true)
                ->whereIn('routing_levels.code', [
                    OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
                    OrganizationCatalog::SECTION_HEAD_LEVEL,
                ])
                ->where('routing_units.code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                ->where('routing_units.is_active', true);
        });
    }

    /** @return array<int, string> */
    public function relations(): array
    {
        return [
            'senderOrganization:id,name',
            'currentDocument.sourceSubmissionDocument.submission',
            'currentDocument.replacesDocument',
            'currentRoute.recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'currentRoute.routedBy:id,name',
            'currentRoute.routedByPositionAssignment.position.organizationalUnit:id,name',
        ];
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

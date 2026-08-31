<?php

namespace App\Services;

use App\Exceptions\LetterRoutingPositionContextConflict;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

class LetterRoutingPositionAssignmentResolver
{
    public function hasRoutingViewingAssignment(User $user): bool
    {
        return $this->routingViewingQuery($user)->exists();
    }

    public function hasRoutingCreatingAssignment(User $user): bool
    {
        return $this->routingCreatingQuery($user)->exists();
    }

    public function hasExecutiveInboxAssignment(User $user): bool
    {
        return $this->executiveInboxQuery($user)->exists();
    }

    public function hasExecutiveAssignmentForPosition(User $user, int $positionId): bool
    {
        return $this->executiveInboxQuery($user)
            ->where('position_id', $positionId)
            ->exists();
    }

    /** @return list<int> */
    public function executivePositionIds(User $user): array
    {
        return array_values($this->executiveInboxQuery($user)
            ->pluck('position_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all());
    }

    public function lockRoutingCreatingAssignment(User $user): PositionAssignment
    {
        $assignments = $this->routingCreatingQuery($user)
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($assignments->isEmpty()) {
            throw LetterRoutingPositionContextConflict::missing();
        }

        if ($assignments->count() > 1) {
            throw LetterRoutingPositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }

    /** @return Builder<PositionAssignment> */
    private function routingViewingQuery(User $user): Builder
    {
        return $this->baseActiveQuery($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->whereIn('code', [
                        OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
                        OrganizationCatalog::SECTION_HEAD_LEVEL,
                    ])
                    ->where('is_active', true))
                ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                    ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                    ->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function routingCreatingQuery(User $user): Builder
    {
        return $this->baseActiveQuery($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL)
                    ->where('is_active', true))
                ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                    ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                    ->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function executiveInboxQuery(User $user): Builder
    {
        return $this->baseActiveQuery($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('code', OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL)
                    ->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function baseActiveQuery(User $user): Builder
    {
        return PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at');
    }
}

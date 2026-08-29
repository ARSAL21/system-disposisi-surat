<?php

namespace App\Services;

use App\Exceptions\IntakePositionContextConflict;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

class IntakePositionAssignmentResolver
{
    public function hasActiveAssignment(User $user): bool
    {
        return $this->query($user)->exists();
    }

    public function lockActiveAssignment(User $user): PositionAssignment
    {
        $assignments = $this->query($user)
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($assignments->isEmpty()) {
            throw IntakePositionContextConflict::missing();
        }

        if ($assignments->count() > 1) {
            throw IntakePositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }

    /** @return Builder<PositionAssignment> */
    private function query(User $user): Builder
    {
        return PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->whereNull('ended_at')
            ->whereHas('position', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $query) => $query
                    ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_LEVEL)
                    ->where('is_active', true)));
    }
}

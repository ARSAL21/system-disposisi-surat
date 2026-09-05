<?php

namespace App\Services;

use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

final class IncomingRegisterPositionAssignmentResolver
{
    public function hasActiveAssignment(User $user): bool
    {
        return PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
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
                    ->where('is_active', true)))
            ->exists();
    }
}

<?php

namespace App\Services;

use App\Enums\LetterActivityVisibility;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

class LetterActivityVisibilityResolver
{
    public function resolve(User $user): LetterActivityVisibility
    {
        return $this->hasBusinessVisibility($user)
            ? LetterActivityVisibility::Details
            : LetterActivityVisibility::Summary;
    }

    private function hasBusinessVisibility(User $user): bool
    {
        return PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->whereNull('ended_at')
            ->whereHas('position', function (Builder $position): void {
                $position
                    ->where('is_active', true)
                    ->where(function (Builder $eligiblePosition): void {
                        $eligiblePosition
                            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                                ->where('code', OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL)
                                ->where('is_active', true))
                            ->orWhere(function (Builder $generalAffairsHead): void {
                                $generalAffairsHead
                                    ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                                        ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL)
                                        ->where('is_active', true))
                                    ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                                        ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                                        ->where('is_active', true));
                            });
                    });
            })
            ->exists();
    }
}

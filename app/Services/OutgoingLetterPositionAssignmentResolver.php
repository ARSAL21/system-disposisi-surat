<?php

namespace App\Services;

use App\Exceptions\LetterResponsePositionContextConflict;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

final class OutgoingLetterPositionAssignmentResolver
{
    public function lockGeneralAffairsOfficer(User $user): PositionAssignment
    {
        return $this->lockOne($this->active($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_LEVEL)
                    ->where('is_active', true))
                ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                    ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                    ->where('is_active', true))));
    }

    public function lockGeneralAffairsHead(User $user): PositionAssignment
    {
        return $this->lockOne($this->active($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL)
                    ->where('is_active', true))
                ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                    ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                    ->where('is_active', true))));
    }

    public function lockPosition(User $user, int $positionId): PositionAssignment
    {
        return $this->lockOne($this->active($user)->where('position_id', $positionId));
    }

    /** @return Builder<PositionAssignment> */
    private function active(User $user): Builder
    {
        $query = PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level->where('is_active', true))
                ->where(function (Builder $unitScope): void {
                    $unitScope->whereNull('organizational_unit_id')
                        ->orWhereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                            ->where('is_active', true));
                }));

        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /** @param Builder<PositionAssignment> $query */
    private function lockOne(Builder $query): PositionAssignment
    {
        $assignments = $query->lockForUpdate()->limit(2)->get();

        if ($assignments->isEmpty()) {
            throw LetterResponsePositionContextConflict::missing();
        }

        if ($assignments->count() > 1) {
            throw LetterResponsePositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }
}

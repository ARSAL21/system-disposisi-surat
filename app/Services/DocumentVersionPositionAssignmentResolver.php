<?php

namespace App\Services;

use App\Exceptions\DocumentVersionPositionContextConflict;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

class DocumentVersionPositionAssignmentResolver
{
    public function hasViewingAssignment(User $user): bool
    {
        return $this->viewingQuery($user)->exists();
    }

    public function hasCreatingAssignment(User $user): bool
    {
        return $this->creatingQuery($user)->exists();
    }

    public function lockCreatingAssignment(User $user): PositionAssignment
    {
        $assignments = $this->creatingQuery($user)
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($assignments->isEmpty()) {
            throw DocumentVersionPositionContextConflict::missing();
        }

        if ($assignments->count() > 1) {
            throw DocumentVersionPositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }

    /** @return Builder<PositionAssignment> */
    private function viewingQuery(User $user): Builder
    {
        return PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->whereNull('ended_at')
            ->whereHas('position', function (Builder $position): void {
                $position
                    ->where('is_active', true)
                    ->where(function (Builder $authority): void {
                        $authority
                            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                                ->where('code', OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL)
                                ->where('is_active', true))
                            ->orWhere(function (Builder $generalAffairs): void {
                                $generalAffairs
                                    ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                                        ->whereIn('code', [
                                            OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
                                            OrganizationCatalog::SECTION_HEAD_LEVEL,
                                        ])
                                        ->where('is_active', true))
                                    ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                                        ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                                        ->where('is_active', true));
                            });
                    });
            });
    }

    /** @return Builder<PositionAssignment> */
    private function creatingQuery(User $user): Builder
    {
        return PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->whereNull('ended_at')
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL)
                    ->where('is_active', true))
                ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                    ->where('code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                    ->where('is_active', true)));
    }
}

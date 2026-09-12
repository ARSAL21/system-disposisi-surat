<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Exceptions\StandaloneOutgoingPositionContextConflict;
use App\Models\OrganizationalUnit;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

final class StandaloneOutgoingPositionAssignmentResolver
{
    public function hasStaffAssignmentForUnit(User $user, int $unitId): bool
    {
        return $this->activeForLevel($user, OrganizationCatalog::UNIT_STAFF_LEVEL)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('organizational_unit_id', $unitId))
            ->exists();
    }

    public function hasSectionHeadAssignmentForUnit(User $user, int $unitId): bool
    {
        return $this->activeForLevel($user, OrganizationCatalog::SECTION_HEAD_LEVEL)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('organizational_unit_id', $unitId))
            ->exists();
    }

    public function hasAssistantForUnit(User $user, int $unitId): bool
    {
        $parentId = OrganizationalUnit::query()->whereKey($unitId)->value('parent_id');

        return $parentId !== null && $this->activeForLevel($user, OrganizationCatalog::ASSISTANT_LEVEL)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('organizational_unit_id', $parentId))
            ->exists();
    }

    public function hasAnyStandaloneScope(User $user): bool
    {
        return $this->active($user)->whereHas('position.positionLevel', fn (Builder $level): Builder => $level
            ->whereIn('code', [
                OrganizationCatalog::UNIT_STAFF_LEVEL,
                OrganizationCatalog::SECTION_HEAD_LEVEL,
                OrganizationCatalog::ASSISTANT_LEVEL,
            ]))->exists();
    }

    public function hasStaffAssignment(User $user): bool
    {
        return $this->activeForLevel($user, OrganizationCatalog::UNIT_STAFF_LEVEL)->exists();
    }

    public function hasSekdaAssignment(User $user): bool
    {
        return $this->active($user)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('code', 'SEKDA'))
            ->exists();
    }

    public function lockStaffAssignmentForUnit(User $user, int $unitId): PositionAssignment
    {
        return $this->lockOne($this->activeForLevel($user, OrganizationCatalog::UNIT_STAFF_LEVEL)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('organizational_unit_id', $unitId)));
    }

    public function lockSectionHeadAssignmentForUnit(User $user, int $unitId): PositionAssignment
    {
        return $this->lockOne($this->activeForLevel($user, OrganizationCatalog::SECTION_HEAD_LEVEL)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('organizational_unit_id', $unitId)));
    }

    /**
     * Resolves the person who may act for a delivered standalone letter.
     * A staff author acts as staff; a Kabag may act only for the same unit.
     */
    public function lockDeliveryActorAssignmentForUnit(User $user, int $unitId): PositionAssignment
    {
        if ($user->can(PermissionName::CreateStandaloneOutgoing->value)
            && $this->hasStaffAssignmentForUnit($user, $unitId)) {
            return $this->lockStaffAssignmentForUnit($user, $unitId);
        }

        if ($user->can(PermissionName::ReviewStandaloneOutgoing->value)
            && $this->hasSectionHeadAssignmentForUnit($user, $unitId)) {
            return $this->lockSectionHeadAssignmentForUnit($user, $unitId);
        }

        throw StandaloneOutgoingPositionContextConflict::missing();
    }

    public function lockAssistantAssignmentForUnit(User $user, int $unitId): PositionAssignment
    {
        $unit = OrganizationalUnit::query()->whereKey($unitId)->lockForUpdate()->firstOrFail();

        if ($unit->parent_id === null) {
            throw StandaloneOutgoingPositionContextConflict::missing();
        }

        return $this->lockOne($this->activeForLevel($user, OrganizationCatalog::ASSISTANT_LEVEL)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('organizational_unit_id', $unit->parent_id)));
    }

    public function lockSekdaAssignment(User $user): PositionAssignment
    {
        return $this->lockOne($this->active($user)
            ->whereHas('position', fn (Builder $position): Builder => $position->where('code', 'SEKDA')));
    }

    /** @return list<int> */
    public function staffUnitIds(User $user): array
    {
        return $this->unitIdsForLevel($user, OrganizationCatalog::UNIT_STAFF_LEVEL);
    }

    /** @return list<int> */
    public function sectionHeadUnitIds(User $user): array
    {
        return $this->unitIdsForLevel($user, OrganizationCatalog::SECTION_HEAD_LEVEL);
    }

    /** @return list<int> */
    public function assistantChildUnitIds(User $user): array
    {
        $assistantUnitIds = $this->unitIdsForLevel($user, OrganizationCatalog::ASSISTANT_LEVEL);

        if ($assistantUnitIds === []) {
            return [];
        }

        return array_values(OrganizationalUnit::query()
            ->whereIn('parent_id', $assistantUnitIds)
            ->where('is_active', true)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all());
    }

    /** @return list<int> */
    public function activePositionIds(User $user): array
    {
        return array_values($this->active($user)->pluck('position_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all());
    }

    /** @return list<int> */
    private function unitIdsForLevel(User $user, string $level): array
    {
        return array_values($this->activeForLevel($user, $level)
            ->join('positions', 'position_assignments.position_id', '=', 'positions.id')
            ->whereNotNull('positions.organizational_unit_id')
            ->pluck('positions.organizational_unit_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all());
    }

    /** @return Builder<PositionAssignment> */
    private function activeForLevel(User $user, string $levelCode): Builder
    {
        return $this->active($user)->whereHas('position.positionLevel', fn (Builder $level): Builder => $level
            ->where('code', $levelCode));
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
                ->whereNotNull('organizational_unit_id')
                ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit->where('is_active', true))
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level->where('is_active', true)));

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
            throw StandaloneOutgoingPositionContextConflict::missing();
        }

        if ($assignments->count() > 1) {
            throw StandaloneOutgoingPositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }
}

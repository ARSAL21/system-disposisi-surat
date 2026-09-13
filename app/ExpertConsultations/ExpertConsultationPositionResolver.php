<?php

namespace App\ExpertConsultations;

use App\Enums\PositionRelationshipType;
use App\Exceptions\DispositionPositionContextConflict;
use App\Exceptions\ExpertConsultationStateConflict;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionRelationship;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ExpertConsultationPositionResolver
{
    public function hasExpertAssignmentForPosition(User $user, int $positionId): bool
    {
        return $this->expertAssignments($user)->where('position_id', $positionId)->exists();
    }

    public function hasCoordinationAssignment(User $user): bool
    {
        return $this->sekdaAssignments($user)->exists();
    }

    public function lockExpertAssignmentForPosition(User $user, int $positionId): PositionAssignment
    {
        return $this->lockOne($this->expertAssignments($user)->where('position_id', $positionId));
    }

    public function lockMayorAssignmentForPosition(User $user, int $positionId): PositionAssignment
    {
        return $this->lockOne($this->mayorAssignments($user)->where('position_id', $positionId));
    }

    public function hasMayorAssignmentForPosition(User $user, int $positionId): bool
    {
        return $this->mayorAssignments($user)->where('position_id', $positionId)->exists();
    }

    /**
     * @param  list<int>  $positionIds
     * @return Collection<int, Position>
     */
    public function lockEligibleExperts(array $positionIds, int $mayorPositionId): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $positionIds)));
        if ($ids === [] || count($ids) > 3 || count($ids) !== count($positionIds)) {
            throw ExpertConsultationStateConflict::invalidHierarchy();
        }

        $positions = Position::query()
            ->with(['positionLevel', 'organizationalUnit', 'activeAssignment.user'])
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        if ($positions->count() !== count($ids)) {
            throw ExpertConsultationStateConflict::invalidHierarchy();
        }

        foreach ($positions as $position) {
            $assignments = PositionAssignment::query()->where('position_id', $position->getKey())
                ->where('started_at', '<=', now())->whereNull('ended_at')->orderBy('id')->lockForUpdate()->get();
            $assignment = $assignments->first();
            if ($position->positionLevel->code !== OrganizationCatalog::EXPERT_ADVISOR_LEVEL
                || ! $position->organizationalUnit?->is_active
                || $assignments->count() !== 1
                || $assignment === null
                || ! $assignment->user->isInternalAccount()
                || ! $assignment->user->is_active
                || ! $assignment->user->hasVerifiedEmail()
                || ! PositionRelationship::query()
                    ->where('source_position_id', $position->getKey())
                    ->where('target_position_id', $mayorPositionId)
                    ->where('relationship_type', PositionRelationshipType::SubstantiveAccountability->value)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->exists()
                || ! PositionRelationship::query()
                    ->where('source_position_id', $position->getKey())
                    ->where('relationship_type', PositionRelationshipType::AdministrativeCoordination->value)
                    ->where('is_active', true)
                    ->whereHas('targetPosition', fn (Builder $target): Builder => $target->where('code', OrganizationCatalog::REGIONAL_SECRETARY_POSITION)->where('is_active', true))
                    ->lockForUpdate()
                    ->exists()) {
                throw ExpertConsultationStateConflict::invalidHierarchy();
            }
        }

        return $positions;
    }

    /** @return Builder<PositionAssignment> */
    private function expertAssignments(User $user): Builder
    {
        return $this->baseAssignments($user)->whereHas('position', fn (Builder $query): Builder => $query
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::EXPERT_ADVISOR_LEVEL)->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function mayorAssignments(User $user): Builder
    {
        return $this->baseAssignments($user)->whereHas('position', fn (Builder $query): Builder => $query
            ->where('code', OrganizationCatalog::MAYOR_POSITION)->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::MAYOR_LEVEL)->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function sekdaAssignments(User $user): Builder
    {
        return $this->baseAssignments($user)->whereHas('position', fn (Builder $query): Builder => $query
            ->where('code', OrganizationCatalog::REGIONAL_SECRETARY_POSITION)->where('is_active', true));
    }

    /** @return Builder<PositionAssignment> */
    private function baseAssignments(User $user): Builder
    {
        $query = PositionAssignment::query()->where('user_id', $user->getKey())->where('started_at', '<=', now())->whereNull('ended_at');

        return $user->isInternalAccount() && $user->is_active && $user->hasVerifiedEmail() ? $query : $query->whereRaw('1 = 0');
    }

    /** @param Builder<PositionAssignment> $query */
    private function lockOne(Builder $query): PositionAssignment
    {
        $assignments = $query->orderBy('id')->lockForUpdate()->limit(2)->get();
        if ($assignments->count() !== 1) {
            throw $assignments->isEmpty() ? DispositionPositionContextConflict::missing() : DispositionPositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }
}

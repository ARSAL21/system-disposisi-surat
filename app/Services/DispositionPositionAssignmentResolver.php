<?php

namespace App\Services;

use App\Exceptions\DispositionPositionContextConflict;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

class DispositionPositionAssignmentResolver
{
    public function hasExecutiveAssignment(User $user): bool
    {
        return $this->executiveQuery($user)->exists();
    }

    public function hasExecutiveAssignmentForPosition(User $user, int $positionId): bool
    {
        return $this->executiveQuery($user)
            ->where('position_id', $positionId)
            ->exists();
    }

    public function lockExecutiveAssignmentForPosition(User $user, int $positionId): PositionAssignment
    {
        return $this->lockExactlyOne(
            $this->executiveQuery($user)->where('position_id', $positionId),
        );
    }

    public function hasInboxAssignment(User $user): bool
    {
        return $this->inboxQuery($user)->exists();
    }

    public function hasAssistantAssignmentForPosition(User $user, int $positionId): bool
    {
        return $this->assistantQuery($user)
            ->where('position_id', $positionId)
            ->exists();
    }

    public function lockAssistantAssignmentForPosition(User $user, int $positionId): PositionAssignment
    {
        return $this->lockExactlyOne(
            $this->assistantQuery($user)->where('position_id', $positionId),
        );
    }

    public function hasInboxAssignmentForPosition(User $user, int $positionId): bool
    {
        return $this->inboxQuery($user)
            ->where('position_id', $positionId)
            ->exists();
    }

    /** @return list<int> */
    public function assistantPositionIds(User $user): array
    {
        return array_values($this->assistantQuery($user)
            ->pluck('position_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all());
    }

    /** @return list<int> */
    public function inboxPositionIds(User $user): array
    {
        return array_values($this->inboxQuery($user)
            ->pluck('position_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all());
    }

    /** @param Builder<PositionAssignment> $query */
    private function lockExactlyOne(Builder $query): PositionAssignment
    {
        $assignments = $query
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($assignments->isEmpty()) {
            throw DispositionPositionContextConflict::missing();
        }

        if ($assignments->count() > 1) {
            throw DispositionPositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }

    /** @return Builder<PositionAssignment> */
    private function executiveQuery(User $user): Builder
    {
        return $this->baseActiveQuery($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('code', OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL)
                    ->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function assistantQuery(User $user): Builder
    {
        return $this->positionLevelQuery($user, OrganizationCatalog::ASSISTANT_LEVEL);
    }

    /** @return Builder<PositionAssignment> */
    private function inboxQuery(User $user): Builder
    {
        return $this->baseActiveQuery($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->whereIn('code', [
                        OrganizationCatalog::ASSISTANT_LEVEL,
                        OrganizationCatalog::SECTION_HEAD_LEVEL,
                    ])
                    ->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function positionLevelQuery(User $user, string $levelCode): Builder
    {
        return $this->baseActiveQuery($user)
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('code', $levelCode)
                    ->where('is_active', true)));
    }

    /** @return Builder<PositionAssignment> */
    private function baseActiveQuery(User $user): Builder
    {
        $query = PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at');

        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

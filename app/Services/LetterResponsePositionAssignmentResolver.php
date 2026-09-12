<?php

namespace App\Services;

use App\Exceptions\LetterResponsePositionContextConflict;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

final class LetterResponsePositionAssignmentResolver
{
    public function hasAssignmentForPosition(User $user, int $positionId): bool
    {
        return $this->query($user)->where('position_id', $positionId)->exists();
    }

    public function lockAssignmentForPosition(User $user, int $positionId): PositionAssignment
    {
        $assignments = $this->query($user)
            ->where('position_id', $positionId)
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($assignments->isEmpty()) {
            throw LetterResponsePositionContextConflict::missing();
        }

        if ($assignments->count() > 1) {
            throw LetterResponsePositionContextConflict::ambiguous();
        }

        return $assignments->firstOrFail();
    }

    /** @return Builder<PositionAssignment> */
    private function query(User $user): Builder
    {
        $query = PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('is_active', true)
                    ->whereIn('code', [
                        ...OrganizationCatalog::executiveLevelCodes(),
                        OrganizationCatalog::ASSISTANT_LEVEL,
                        OrganizationCatalog::SECTION_HEAD_LEVEL,
                    ])));

        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}

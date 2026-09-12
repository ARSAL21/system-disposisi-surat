<?php

namespace App\StandaloneOutgoing;

use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Database\Eloquent\Builder;

final class StandaloneOutgoingScopeQuery
{
    public function __construct(private readonly StandaloneOutgoingPositionAssignmentResolver $resolver) {}

    /** @return Builder<StandaloneOutgoingDraft> */
    public function visibleTo(User $user): Builder
    {
        $staffUnitIds = $this->resolver->staffUnitIds($user);
        $copyPositionIds = $this->resolver->activePositionIds($user);
        $reviewUnitIds = array_values(array_unique([
            ...$this->resolver->sectionHeadUnitIds($user),
            ...$this->resolver->assistantChildUnitIds($user),
        ]));

        $query = StandaloneOutgoingDraft::query();
        if ($staffUnitIds === [] && $reviewUnitIds === [] && $copyPositionIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $visibility) use ($user, $staffUnitIds, $reviewUnitIds, $copyPositionIds): void {
            $hasClause = false;
            if ($reviewUnitIds !== []) {
                $visibility->whereIn('organizational_unit_id', $reviewUnitIds);
                $hasClause = true;
            }

            if ($staffUnitIds !== []) {
                $method = $hasClause ? 'orWhere' : 'where';
                $visibility->{$method}(function (Builder $ownDrafts) use ($user, $staffUnitIds): void {
                    $ownDrafts->where('created_by_user_id', $user->getKey())
                        ->whereIn('organizational_unit_id', $staffUnitIds);
                });
                $hasClause = true;
            }

            if ($copyPositionIds !== []) {
                $method = $hasClause ? 'orWhereHas' : 'whereHas';
                $visibility->{$method}('copyRecipients', fn (Builder $copy): Builder => $copy->whereIn('position_id', $copyPositionIds));
            }
        });
    }

    public function canView(User $user, StandaloneOutgoingDraft $draft): bool
    {
        return $this->visibleTo($user)->whereKey($draft->getKey())->exists();
    }

    public function hasBusinessScope(User $user): bool
    {
        return $this->resolver->hasAnyStandaloneScope($user);
    }
}

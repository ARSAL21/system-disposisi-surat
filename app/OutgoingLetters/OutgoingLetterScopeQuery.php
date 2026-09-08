<?php

namespace App\OutgoingLetters;

use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class OutgoingLetterScopeQuery
{
    public function __construct(private readonly OutgoingLetterScopeResolver $resolver) {}

    /** @return Builder<OutgoingLetter> */
    public function visibleTo(User $user): Builder
    {
        $scope = $this->resolver->resolve($user);
        $query = OutgoingLetter::query();

        if ($scope === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($scope->hasGlobalRegisterAccess()) {
            return $query;
        }

        return $query->where(function (Builder $visibility) use ($scope): void {
            $hasClause = false;

            if ($scope->executivePositionIds !== []) {
                $visibility->whereHas('incomingLetter.routes', fn (Builder $route): Builder => $route
                    ->whereIn('recipient_position_id', $scope->executivePositionIds));
                $hasClause = true;
            }

            foreach ([$scope->assistantPositionIds, $scope->sectionHeadPositionIds] as $positionIds) {
                if ($positionIds === []) {
                    continue;
                }

                $method = $hasClause ? 'orWhere' : 'where';
                $visibility->{$method}(function (Builder $contribution) use ($positionIds): void {
                    $contribution
                        ->whereHas('sourceDocumentVersion.document', fn (Builder $document): Builder => $document
                            ->whereIn('owner_position_id', $positionIds))
                        ->orWhereHas('sourceDocumentVersion.sourceVersions.document', fn (Builder $document): Builder => $document
                            ->whereIn('owner_position_id', $positionIds))
                        ->orWhereHas('sourceDocumentVersion.sourceVersions.sourceVersions.document', fn (Builder $document): Builder => $document
                            ->whereIn('owner_position_id', $positionIds));
                });
                $hasClause = true;
            }

            if (! $hasClause) {
                $visibility->whereRaw('1 = 0');
            }
        });
    }

    public function canView(User $user, OutgoingLetter $letter): bool
    {
        return $this->visibleTo($user)->whereKey($letter->getKey())->exists();
    }

    public function hasBusinessScope(User $user): bool
    {
        return $this->resolver->resolve($user) !== null;
    }
}

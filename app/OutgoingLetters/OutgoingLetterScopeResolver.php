<?php

namespace App\OutgoingLetters;

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class OutgoingLetterScopeResolver
{
    public function resolve(User $user): ?OutgoingLetterScope
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return null;
        }

        /** @var Collection<int, PositionAssignment> $assignments */
        $assignments = PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level->where('is_active', true)))
            ->with([
                'position.positionLevel:id,code,is_active',
                'position.organizationalUnit:id,code,is_active',
            ])
            ->get();

        $positions = $assignments
            ->map(fn (PositionAssignment $assignment): Position => $assignment->position)
            ->filter(fn (Position $position): bool => $position->organizationalUnit === null
                || $position->organizationalUnit->is_active)
            ->unique('id')
            ->values();

        if ($positions->isEmpty()) {
            return null;
        }

        $idsAtLevel = fn (string $level): array => array_values($positions
            ->filter(fn (Position $position): bool => $position->positionLevel->code === $level)
            ->map(fn (Position $position): int => (int) $position->getKey())
            ->all());
        $generalAffairsHeads = $positions->filter(fn (Position $position): bool => $position->positionLevel->code === OrganizationCatalog::SECTION_HEAD_LEVEL
            && $position->organizationalUnit?->code === OrganizationCatalog::GENERAL_AFFAIRS_UNIT);

        return new OutgoingLetterScope(
            positionIds: array_values($positions->map(fn (Position $position): int => (int) $position->getKey())->all()),
            executivePositionIds: array_values($positions
                ->filter(fn (Position $position): bool => in_array($position->positionLevel->code, OrganizationCatalog::executiveLevelCodes(), true))
                ->map(fn (Position $position): int => (int) $position->getKey())
                ->all()),
            assistantPositionIds: $idsAtLevel(OrganizationCatalog::ASSISTANT_LEVEL),
            sectionHeadPositionIds: $idsAtLevel(OrganizationCatalog::SECTION_HEAD_LEVEL),
            generalAffairsOfficerPositionIds: array_values($positions
                ->filter(fn (Position $position): bool => $position->positionLevel->code === OrganizationCatalog::GENERAL_AFFAIRS_LEVEL
                    && $position->organizationalUnit?->code === OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                ->map(fn (Position $position): int => (int) $position->getKey())
                ->all()),
            generalAffairsHeadPositionIds: array_values($generalAffairsHeads
                ->map(fn (Position $position): int => (int) $position->getKey())
                ->all()),
        );
    }
}

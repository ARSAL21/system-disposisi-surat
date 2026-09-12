<?php

namespace App\Reporting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ReportBranchVisibility
{
    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function firstRecipients(Builder|Relation $query, ReportScope $scope, bool $forAggregates): Builder|Relation
    {
        if (($forAggregates && $scope->hasGlobalAggregates)
            || (! $forAggregates && $scope->hasGlobalDetails)) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($scope): void {
            if ($scope->assistantPositionIds !== []) {
                // Direct route: the first recipient is the Assistant. Via
                // Wali Kota: the first recipient is Sekda and the Assistant
                // is one level below it.
                $visible->whereIn('recipient_position_id', $scope->assistantPositionIds)
                    ->orWhereHas('childDispositions.recipients', fn (Builder $recipient): Builder => $recipient
                        ->whereIn('recipient_position_id', $scope->assistantPositionIds));
            }

            if ($scope->sectionHeadPositionIds !== []) {
                // A section head may be reached directly below an Assistant,
                // or below Sekda -> Assistant on a Wali Kota route.
                $visible->orWhereHas('childDispositions.recipients', fn (Builder $recipient): Builder => $recipient
                    ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds)
                    ->orWhereHas('childDispositions.recipients', fn (Builder $terminal): Builder => $terminal
                        ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds)));
            }
        });
    }

    /**
     * Filter the level immediately below the first recipient. It is a section
     * head for a direct Sekda route and an Assistant for a Wali Kota route.
     * The predicate preserves enough structural context for the presenter
     * without loading a sibling branch.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function intermediateRecipients(Builder|Relation $query, ReportScope $scope, bool $forAggregates): Builder|Relation
    {
        if (($forAggregates && $scope->hasGlobalAggregates)
            || (! $forAggregates && $scope->hasGlobalDetails)) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($scope): void {
            if ($scope->assistantPositionIds !== []) {
                $visible->whereIn('recipient_position_id', $scope->assistantPositionIds)
                    ->orWhereHas('disposition.parentRecipient', fn (Builder $parent): Builder => $parent
                        ->whereIn('recipient_position_id', $scope->assistantPositionIds));
            }

            if ($scope->sectionHeadPositionIds !== []) {
                $visible->orWhereIn('recipient_position_id', $scope->sectionHeadPositionIds)
                    ->orWhereHas('childDispositions.recipients', fn (Builder $terminal): Builder => $terminal
                        ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds));
            }
        });
    }

    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function terminalRecipients(Builder|Relation $query, ReportScope $scope, bool $forAggregates): Builder|Relation
    {
        if (($forAggregates && $scope->hasGlobalAggregates)
            || (! $forAggregates && $scope->hasGlobalDetails)) {
            return $query;
        }

        if ($scope->assistantPositionIds !== [] && $scope->sectionHeadPositionIds !== []) {
            return $query->where(fn (Builder $visible): Builder => $visible
                ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds)
                ->orWhereHas('disposition.parentRecipient', fn (Builder $parent): Builder => $parent
                    ->whereIn('recipient_position_id', $scope->assistantPositionIds)));
        }

        if ($scope->assistantPositionIds !== []) {
            return $query->whereHas('disposition.parentRecipient', fn (Builder $parent): Builder => $parent
                ->whereIn('recipient_position_id', $scope->assistantPositionIds));
        }

        return $query->whereIn('recipient_position_id', $scope->sectionHeadPositionIds);
    }
}

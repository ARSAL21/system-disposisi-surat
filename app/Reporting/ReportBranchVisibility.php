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

        if ($scope->assistantPositionIds !== [] && $scope->sectionHeadPositionIds !== []) {
            return $query->where(fn (Builder $visible): Builder => $visible
                ->whereIn('recipient_position_id', $scope->assistantPositionIds)
                ->orWhereHas('childDispositions.recipients', fn (Builder $recipient): Builder => $recipient
                    ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds)));
        }

        if ($scope->assistantPositionIds !== []) {
            return $query->whereIn('recipient_position_id', $scope->assistantPositionIds);
        }

        return $query->whereHas('childDispositions.recipients', fn (Builder $recipient): Builder => $recipient
            ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds));
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

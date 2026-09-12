<?php

namespace App\OutgoingLetters;

use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use App\StandaloneOutgoing\StandaloneOutgoingScopeQuery;
use Illuminate\Database\Eloquent\Builder;

final class OutgoingLetterScopeQuery
{
    public function __construct(
        private readonly OutgoingLetterScopeResolver $resolver,
        private readonly StandaloneOutgoingScopeQuery $standaloneScope,
        private readonly StandaloneOutgoingPositionAssignmentResolver $standaloneAssignmentResolver,
    ) {}

    /** @return Builder<OutgoingLetter> */
    public function visibleTo(User $user): Builder
    {
        $scope = $this->resolver->resolve($user);
        $query = OutgoingLetter::query();

        return $query->where(function (Builder $root) use ($scope, $user): void {
            $root->where(function (Builder $response) use ($scope): void {
                $response->where('origin', OutgoingLetterOrigin::Response->value);
                if ($scope === null) {
                    $response->whereRaw('1 = 0');

                    return;
                }
                if ($scope->hasGlobalRegisterAccess()) {
                    return;
                }
                $response->where(function (Builder $visibility) use ($scope): void {
                    $this->applyResponseVisibility($visibility, $scope);
                });
            });
            $root->orWhere(function (Builder $standalone) use ($scope, $user): void {
                $standalone->where('origin', OutgoingLetterOrigin::Standalone->value)
                    ->where(function (Builder $visibility) use ($scope, $user): void {
                        $hasAccess = false;
                        if ($scope?->hasGlobalRegisterAccess()) {
                            $visibility->whereRaw('1 = 1');
                            $hasAccess = true;
                        }
                        if ($this->standaloneScope->hasBusinessScope($user)) {
                            $method = $hasAccess ? 'orWhereIn' : 'whereIn';
                            $visibility->{$method}('standalone_outgoing_draft_id', $this->standaloneScope->visibleTo($user)->select('id'));
                            $hasAccess = true;
                        }
                        if ($this->standaloneAssignmentResolver->hasSekdaAssignment($user)) {
                            $method = $hasAccess ? 'orWhereIn' : 'whereIn';
                            $visibility->{$method}('status', [
                                OutgoingLetterStatus::NumberAssigned->value,
                                OutgoingLetterStatus::SekdaReview->value,
                                OutgoingLetterStatus::AwaitingManualSignature->value,
                                OutgoingLetterStatus::ManualScanReview->value,
                                OutgoingLetterStatus::ReadyForDelivery->value,
                                OutgoingLetterStatus::RevisionRequired->value,
                                OutgoingLetterStatus::Delivered->value,
                            ]);
                            $hasAccess = true;
                        }
                        if (! $hasAccess) {
                            $visibility->whereRaw('1 = 0');
                        }
                    });
            });
        });
    }

    /** @param Builder<OutgoingLetter> $visibility */
    private function applyResponseVisibility(Builder $visibility, OutgoingLetterScope $scope): void
    {
        $hasClause = false;

        if ($scope->executivePositionIds !== []) {
            $visibility->where(function (Builder $executive) use ($scope): void {
                $executive->whereHas('incomingLetter.routes', fn (Builder $route): Builder => $route
                    ->whereIn('recipient_position_id', $scope->executivePositionIds))
                    ->orWhereHas('incomingLetter.dispositions.recipients', fn (Builder $recipient): Builder => $recipient
                        ->whereIn('recipient_position_id', $scope->executivePositionIds)
                        ->whereHas('recipientPosition', fn (Builder $position): Builder => $position
                            ->where('code', OrganizationCatalog::REGIONAL_SECRETARY_POSITION)
                            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                                ->where('code', OrganizationCatalog::REGIONAL_SECRETARY_LEVEL)))
                        ->whereHas('disposition.sourceRoute.recipientPosition', fn (Builder $position): Builder => $position
                            ->where('code', OrganizationCatalog::MAYOR_POSITION)
                            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                                ->where('code', OrganizationCatalog::MAYOR_LEVEL))));
            });
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
    }

    public function canView(User $user, OutgoingLetter $letter): bool
    {
        return $this->visibleTo($user)->whereKey($letter->getKey())->exists();
    }

    public function hasBusinessScope(User $user): bool
    {
        return $this->resolver->resolve($user) !== null
            || $this->standaloneScope->hasBusinessScope($user)
            || $this->standaloneAssignmentResolver->hasSekdaAssignment($user);
    }
}

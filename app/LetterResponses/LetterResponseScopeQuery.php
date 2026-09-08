<?php

namespace App\LetterResponses;

use App\Enums\LetterResponseDocumentKind;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\User;
use App\Reporting\ReportScopeResolver;
use Illuminate\Database\Eloquent\Builder;

final class LetterResponseScopeQuery
{
    public function __construct(private readonly ReportScopeResolver $scopeResolver) {}

    /** @return Builder<LetterResponseDossier> */
    public function visibleTo(User $user): Builder
    {
        $scope = $this->scopeResolver->resolve($user);
        $query = LetterResponseDossier::query();

        if ($scope === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $visibility) use ($scope): void {
            if ($scope->executivePositionIds !== []) {
                $visibility->whereHas('incomingLetter.routes', fn (Builder $route): Builder => $route
                    ->whereIn('recipient_position_id', $scope->executivePositionIds));
            }

            if ($scope->assistantPositionIds !== []) {
                $assistantScope = fn (Builder $recipient): Builder => $recipient
                    ->whereIn('recipient_position_id', $scope->assistantPositionIds);

                if ($scope->executivePositionIds === []) {
                    $visibility->whereHas('incomingLetter.dispositions.recipients', $assistantScope);
                } else {
                    $visibility->orWhereHas('incomingLetter.dispositions.recipients', $assistantScope);
                }
            }

            if ($scope->sectionHeadPositionIds !== []) {
                $sectionScope = fn (Builder $recipient): Builder => $recipient
                    ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds);

                if ($scope->executivePositionIds === [] && $scope->assistantPositionIds === []) {
                    $visibility->whereHas('incomingLetter.dispositions.recipients', $sectionScope);
                } else {
                    $visibility->orWhereHas('incomingLetter.dispositions.recipients', $sectionScope);
                }
            }

            if ($scope->executivePositionIds === []
                && $scope->assistantPositionIds === []
                && $scope->sectionHeadPositionIds === []) {
                $visibility->whereRaw('1 = 0');
            }
        });
    }

    public function canView(User $user, LetterResponseDossier $dossier): bool
    {
        return $this->visibleTo($user)->whereKey($dossier->getKey())->exists();
    }

    public function hasBusinessScope(User $user): bool
    {
        return $this->scopeResolver->resolve($user) !== null;
    }

    public function canViewDocumentVersion(
        User $user,
        LetterResponseDossier $dossier,
        LetterResponseDocumentVersion $version,
    ): bool {
        if (! $this->canView($user, $dossier)
            || ! $version->document()->where('letter_response_dossier_id', $dossier->getKey())->exists()) {
            return false;
        }

        $scope = $this->scopeResolver->resolve($user);

        if ($scope === null) {
            return false;
        }

        if ($scope->executivePositionIds !== []
            && $dossier->incomingLetter->routes()
                ->whereIn('recipient_position_id', $scope->executivePositionIds)
                ->exists()) {
            return true;
        }

        $document = $version->document;

        if ($scope->sectionHeadPositionIds !== []
            && $document->kind === LetterResponseDocumentKind::TechnicalMaterial
            && in_array($document->owner_position_id, $scope->sectionHeadPositionIds, true)) {
            return true;
        }

        if ($scope->assistantPositionIds === []) {
            return false;
        }

        if ($document->kind === LetterResponseDocumentKind::AssistantProposal
            && in_array($document->owner_position_id, $scope->assistantPositionIds, true)) {
            return true;
        }

        return $document->kind === LetterResponseDocumentKind::TechnicalMaterial
            && $document->sourceRecipient()->whereHas(
                'disposition.parentRecipient',
                fn (Builder $recipient): Builder => $recipient
                    ->whereIn('recipient_position_id', $scope->assistantPositionIds),
            )->exists();
    }
}

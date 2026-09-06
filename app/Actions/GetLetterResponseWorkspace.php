<?php

namespace App\Actions;

use App\LetterResponses\LetterResponsePresenter;
use App\LetterResponses\LetterResponseScopeQuery;
use App\Models\LetterResponseDossier;
use App\Models\User;

final class GetLetterResponseWorkspace
{
    public function __construct(
        private readonly LetterResponseScopeQuery $scopeQuery,
        private readonly LetterResponsePresenter $presenter,
    ) {}

    /** @return list<array<string, mixed>> */
    public function index(User $user): array
    {
        return array_values($this->scopeQuery->visibleTo($user)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (LetterResponseDossier $dossier): array => $this->presenter->listItem($dossier, $user))
            ->all());
    }

    /** @return array<string, mixed> */
    public function show(LetterResponseDossier $dossier, User $user): array
    {
        return $this->presenter->dossier($dossier, $user);
    }
}

<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\LetterResponses\LetterResponseScopeQuery;
use App\Models\LetterResponseDossier;
use App\Models\User;
use App\Services\LetterResponsePositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

final class LetterResponseDossierPolicy
{
    public function __construct(
        private readonly LetterResponseScopeQuery $scopeQuery,
        private readonly LetterResponsePositionAssignmentResolver $assignmentResolver,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->authorizePermission($user, PermissionName::ViewLetterResponses)
            ?? ($this->scopeQuery->hasBusinessScope($user) ? Response::allow() : Response::denyAsNotFound());
    }

    public function view(User $user, LetterResponseDossier $dossier): Response
    {
        return $this->authorizeDossier($user, $dossier, PermissionName::ViewLetterResponses);
    }

    public function contribute(User $user, LetterResponseDossier $dossier): Response
    {
        return $this->authorizeDossier($user, $dossier, PermissionName::ContributeLetterResponses);
    }

    public function contributeForPosition(User $user, LetterResponseDossier $dossier, int $positionId): Response
    {
        return $this->authorizeForPosition(
            $user,
            $dossier,
            $positionId,
            PermissionName::ContributeLetterResponses,
        );
    }

    public function review(User $user, LetterResponseDossier $dossier): Response
    {
        return $this->authorizeDossier($user, $dossier, PermissionName::ReviewLetterResponses);
    }

    public function reviewForPosition(User $user, LetterResponseDossier $dossier, int $positionId): Response
    {
        return $this->authorizeForPosition(
            $user,
            $dossier,
            $positionId,
            PermissionName::ReviewLetterResponses,
        );
    }

    public function authorizeResponse(User $user, LetterResponseDossier $dossier): Response
    {
        $authorization = $this->authorizeDossier($user, $dossier, PermissionName::AuthorizeLetterResponses);

        if ($authorization->denied()) {
            return $authorization;
        }

        $positionId = (int) $dossier->incomingLetter->routes()
            ->orderBy('id')
            ->value('recipient_position_id');

        return $positionId > 0 && $this->assignmentResolver->hasAssignmentForPosition($user, $positionId)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function authorizeDossier(
        User $user,
        LetterResponseDossier $dossier,
        PermissionName $permission,
    ): Response {
        $permissionDenial = $this->authorizePermission($user, $permission);

        if ($permissionDenial instanceof Response) {
            return $permissionDenial;
        }

        return $this->scopeQuery->canView($user, $dossier)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function authorizePermission(User $user, PermissionName $permission): ?Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission->value)
            ? null
            : Response::deny('You do not have permission to access letter response dossiers.');
    }

    private function authorizeForPosition(
        User $user,
        LetterResponseDossier $dossier,
        int $positionId,
        PermissionName $permission,
    ): Response {
        $authorization = $this->authorizeDossier($user, $dossier, $permission);

        if ($authorization->denied()) {
            return $authorization;
        }

        return $this->assignmentResolver->hasAssignmentForPosition($user, $positionId)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

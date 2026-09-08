<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\OutgoingLetters\OutgoingLetterScopeQuery;
use App\OutgoingLetters\OutgoingLetterScopeResolver;
use Illuminate\Auth\Access\Response;

final class OutgoingLetterPolicy
{
    public function __construct(
        private readonly OutgoingLetterScopeQuery $scopeQuery,
        private readonly OutgoingLetterScopeResolver $scopeResolver,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->permission($user, PermissionName::ViewOutgoingRegister)
            ?? ($this->scopeQuery->hasBusinessScope($user) ? Response::allow() : Response::denyAsNotFound());
    }

    public function view(User $user, OutgoingLetter $letter): Response
    {
        $denial = $this->permission($user, PermissionName::ViewOutgoingRegister);

        if ($denial instanceof Response) {
            return $denial;
        }

        return $this->scopeQuery->canView($user, $letter)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function assignNumber(User $user, OutgoingLetter $letter): Response
    {
        return $this->generalAffairsAction($user, $letter, PermissionName::NumberOutgoingLetters, officer: true);
    }

    public function uploadSignedDocument(User $user, OutgoingLetter $letter): Response
    {
        $view = $this->view($user, $letter);

        if ($view->denied()) {
            return $view;
        }

        $scope = $this->scopeResolver->resolve($user);
        if ($scope === null) {
            return Response::denyAsNotFound();
        }
        $isOfficer = $user->can(PermissionName::NumberOutgoingLetters->value)
            && $scope->generalAffairsOfficerPositionIds !== [];
        $ownerPositionId = (int) $letter->sourceDocumentVersion()
            ->join('letter_response_documents', 'letter_response_documents.id', '=', 'letter_response_document_versions.letter_response_document_id')
            ->value('letter_response_documents.owner_position_id');
        $isOwner = $user->can(PermissionName::ContributeLetterResponses->value)
            && in_array($ownerPositionId, $scope->positionIds, true);

        if (! $isOfficer && ! $isOwner) {
            return $user->can(PermissionName::NumberOutgoingLetters->value)
                || $user->can(PermissionName::ContributeLetterResponses->value)
                ? Response::denyAsNotFound()
                : Response::deny('You do not have permission to upload outgoing letter documents.');
        }

        return Response::allow();
    }

    public function verify(User $user, OutgoingLetter $letter): Response
    {
        return $this->generalAffairsAction($user, $letter, PermissionName::VerifyOutgoingLetters, officer: false);
    }

    public function deliver(User $user, OutgoingLetter $letter): Response
    {
        return $this->generalAffairsAction($user, $letter, PermissionName::DeliverOutgoingLetters, officer: true);
    }

    public function withdraw(User $user, OutgoingLetter $letter): Response
    {
        $denial = $this->permission($user, PermissionName::AuthorizeLetterResponses);

        if ($denial instanceof Response) {
            return $denial;
        }

        $scope = $this->scopeResolver->resolve($user);
        $executivePositionId = (int) $letter->incomingLetter?->routes()->orderBy('id')->value('recipient_position_id');

        return $scope !== null
            && $executivePositionId > 0
            && in_array($executivePositionId, $scope->executivePositionIds, true)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function generalAffairsAction(
        User $user,
        OutgoingLetter $letter,
        PermissionName $permission,
        bool $officer,
    ): Response {
        $denial = $this->permission($user, $permission);

        if ($denial instanceof Response) {
            return $denial;
        }

        if (! $this->scopeQuery->canView($user, $letter)) {
            return Response::denyAsNotFound();
        }

        $scope = $this->scopeResolver->resolve($user);
        $positionIds = $officer
            ? $scope?->generalAffairsOfficerPositionIds
            : $scope?->generalAffairsHeadPositionIds;

        return $positionIds !== null && $positionIds !== []
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function permission(User $user, PermissionName $permission): ?Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission->value)
            ? null
            : Response::deny('You do not have permission to access outgoing letters.');
    }
}

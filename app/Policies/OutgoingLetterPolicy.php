<?php

namespace App\Policies;

use App\Enums\OutgoingLetterOrigin;
use App\Enums\PermissionName;
use App\LetterResponses\LetterResponseSekdaPositionResolver;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\OutgoingLetters\OutgoingLetterScopeQuery;
use App\OutgoingLetters\OutgoingLetterScopeResolver;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

final class OutgoingLetterPolicy
{
    public function __construct(
        private readonly OutgoingLetterScopeQuery $scopeQuery,
        private readonly OutgoingLetterScopeResolver $scopeResolver,
        private readonly OutgoingLetterPositionAssignmentResolver $outgoingAssignmentResolver,
        private readonly StandaloneOutgoingPositionAssignmentResolver $standaloneAssignmentResolver,
        private readonly LetterResponseSekdaPositionResolver $sekdaPositionResolver,
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
        if ($letter->origin !== OutgoingLetterOrigin::Response) {
            return Response::denyAsNotFound();
        }

        return $this->generalAffairsAction($user, $letter, PermissionName::NumberOutgoingLetters, officer: true);
    }

    public function uploadSignedDocument(User $user, OutgoingLetter $letter): Response
    {
        if ($letter->origin !== OutgoingLetterOrigin::Response) {
            return Response::denyAsNotFound();
        }

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
        if ($letter->origin !== OutgoingLetterOrigin::Response) {
            return Response::denyAsNotFound();
        }

        return $this->generalAffairsAction($user, $letter, PermissionName::VerifyOutgoingLetters, officer: false);
    }

    public function deliver(User $user, OutgoingLetter $letter): Response
    {
        if ($letter->origin === OutgoingLetterOrigin::Standalone) {
            return $this->standaloneDeliveryPermission($user, $letter);
        }

        return $this->generalAffairsAction($user, $letter, PermissionName::DeliverOutgoingLetters, officer: true);
    }

    public function createStandaloneCorrection(User $user, OutgoingLetter $letter): Response
    {
        return $this->standaloneDeliveryPermission($user, $letter);
    }

    public function resendStandaloneDeliveryEmail(User $user, OutgoingLetter $letter): Response
    {
        return $this->standaloneDeliveryPermission($user, $letter);
    }

    public function revokeStandaloneDeliveryEmail(User $user, OutgoingLetter $letter): Response
    {
        return $this->standaloneDeliveryPermission($user, $letter);
    }

    public function withdraw(User $user, OutgoingLetter $letter): Response
    {
        if ($letter->origin !== OutgoingLetterOrigin::Response) {
            return Response::denyAsNotFound();
        }

        $denial = $this->permission($user, PermissionName::AuthorizeLetterResponses);

        if ($denial instanceof Response) {
            return $denial;
        }

        $scope = $this->scopeResolver->resolve($user);
        $executivePositionId = $letter->incomingLetter === null
            ? null
            : $this->sekdaPositionResolver->positionId($letter->incomingLetter);

        return $scope !== null
            && $executivePositionId !== null
            && in_array($executivePositionId, $scope->executivePositionIds, true)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function submitStandaloneToSekda(User $user, OutgoingLetter $letter): Response
    {
        $denial = $this->permission($user, PermissionName::NumberOutgoingLetters);

        if ($denial instanceof Response) {
            return $denial;
        }

        return $letter->origin->value === 'STANDALONE'
            && $this->outgoingAssignmentResolver->hasGeneralAffairsOfficer($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function approveStandaloneBySekda(User $user, OutgoingLetter $letter): Response
    {
        $denial = $this->permission($user, PermissionName::ApproveStandaloneOutgoing);

        if ($denial instanceof Response) {
            return $denial;
        }

        return $letter->origin->value === 'STANDALONE'
            && $this->standaloneAssignmentResolver->hasSekdaAssignment($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function uploadStandaloneManualScan(User $user, OutgoingLetter $letter): Response
    {
        $draft = $letter->standaloneDraft;
        if ($draft === null || $letter->origin->value !== 'STANDALONE') {
            return Response::denyAsNotFound();
        }

        if ($user->can(PermissionName::CreateStandaloneOutgoing->value)
            && $this->standaloneAssignmentResolver->hasStaffAssignmentForUnit($user, $draft->organizational_unit_id)) {
            return Response::allow();
        }

        if ($user->can(PermissionName::ReviewStandaloneOutgoing->value)
            && $this->standaloneAssignmentResolver->hasSectionHeadAssignmentForUnit($user, $draft->organizational_unit_id)) {
            return Response::allow();
        }

        return $this->hasAnyStandaloneUploadPermission($user)
            ? Response::denyAsNotFound()
            : Response::deny('Anda tidak memiliki hak untuk mengunggah scan tanda tangan.');
    }

    public function reviewStandaloneManualScan(User $user, OutgoingLetter $letter): Response
    {
        $denial = $this->permission($user, PermissionName::ReviewStandaloneOutgoing);
        if ($denial instanceof Response) {
            return $denial;
        }

        $draft = $letter->standaloneDraft;

        return $letter->origin->value === 'STANDALONE'
            && $draft !== null
            && $this->standaloneAssignmentResolver->hasSectionHeadAssignmentForUnit($user, $draft->organizational_unit_id)
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

    private function standaloneDeliveryPermission(User $user, OutgoingLetter $letter): Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        $draft = $letter->standaloneDraft;
        if ($letter->origin !== OutgoingLetterOrigin::Standalone || $draft === null) {
            return Response::denyAsNotFound();
        }

        if ($user->can(PermissionName::CreateStandaloneOutgoing->value)
            && (int) $draft->created_by_user_id === (int) $user->getKey()
            && $this->standaloneAssignmentResolver->hasStaffAssignmentForUnit($user, $draft->organizational_unit_id)) {
            return Response::allow();
        }

        if ($user->can(PermissionName::ReviewStandaloneOutgoing->value)
            && $this->standaloneAssignmentResolver->hasSectionHeadAssignmentForUnit($user, $draft->organizational_unit_id)) {
            return Response::allow();
        }

        return $user->can(PermissionName::CreateStandaloneOutgoing->value)
            || $user->can(PermissionName::ReviewStandaloneOutgoing->value)
            ? Response::denyAsNotFound()
            : Response::deny('Anda tidak memiliki hak untuk mengirim surat keluar mandiri.');
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

    private function hasAnyStandaloneUploadPermission(User $user): bool
    {
        return $user->can(PermissionName::CreateStandaloneOutgoing->value)
            || $user->can(PermissionName::ReviewStandaloneOutgoing->value);
    }
}

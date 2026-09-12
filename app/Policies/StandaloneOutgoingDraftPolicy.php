<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use App\StandaloneOutgoing\StandaloneOutgoingScopeQuery;
use Illuminate\Auth\Access\Response;

final class StandaloneOutgoingDraftPolicy
{
    public function __construct(
        private readonly StandaloneOutgoingScopeQuery $scopeQuery,
        private readonly StandaloneOutgoingPositionAssignmentResolver $resolver,
        private readonly OutgoingLetterPositionAssignmentResolver $outgoingResolver,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->permission($user, PermissionName::ViewStandaloneOutgoing)
            ?? ($this->scopeQuery->hasBusinessScope($user) ? Response::allow() : Response::denyAsNotFound());
    }

    public function view(User $user, StandaloneOutgoingDraft $draft): Response
    {
        return $this->permission($user, PermissionName::ViewStandaloneOutgoing)
            ?? ($this->scopeQuery->canView($user, $draft) ? Response::allow() : Response::denyAsNotFound());
    }

    public function create(User $user): Response
    {
        return $this->permission($user, PermissionName::CreateStandaloneOutgoing)
            ?? ($this->resolver->hasStaffAssignment($user) ? Response::allow() : Response::denyAsNotFound());
    }

    public function edit(User $user, StandaloneOutgoingDraft $draft): Response
    {
        if ((int) $draft->created_by_user_id !== (int) $user->getKey()) {
            return Response::denyAsNotFound();
        }

        if ($user->can(PermissionName::CreateStandaloneOutgoing->value)
            && $this->resolver->hasStaffAssignmentForUnit($user, $draft->organizational_unit_id)) {
            return Response::allow();
        }

        if ($user->can(PermissionName::ReviewStandaloneOutgoing->value)
            && $this->resolver->hasSectionHeadAssignmentForUnit($user, $draft->organizational_unit_id)) {
            return Response::allow();
        }

        return $user->can(PermissionName::CreateStandaloneOutgoing->value)
            || $user->can(PermissionName::ReviewStandaloneOutgoing->value)
            ? Response::denyAsNotFound()
            : Response::deny('Anda tidak memiliki hak untuk memperbarui konsep surat keluar.');
    }

    public function reviewSection(User $user, StandaloneOutgoingDraft $draft): Response
    {
        return $this->reviewPermission($user, $draft, $this->resolver->hasSectionHeadAssignmentForUnit($user, $draft->organizational_unit_id));
    }

    public function reviewAssistant(User $user, StandaloneOutgoingDraft $draft): Response
    {
        return $this->reviewPermission($user, $draft, $this->resolver->hasAssistantForUnit($user, $draft->organizational_unit_id));
    }

    public function assignNumber(User $user, StandaloneOutgoingDraft $draft): Response
    {
        $permission = $this->permission($user, PermissionName::NumberOutgoingLetters);
        if ($permission instanceof Response) {
            return $permission;
        }

        return $this->outgoingResolver->hasGeneralAffairsOfficer($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function reviewPermission(User $user, StandaloneOutgoingDraft $draft, bool $hasPosition): Response
    {
        $permission = $this->permission($user, PermissionName::ReviewStandaloneOutgoing);
        if ($permission instanceof Response) {
            return $permission;
        }

        return $hasPosition ? Response::allow() : Response::denyAsNotFound();
    }

    private function permission(User $user, PermissionName $permission): ?Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission->value) ? null : Response::deny('Anda tidak memiliki hak akses surat keluar mandiri.');
    }
}

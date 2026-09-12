<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PermissionName::ViewUsers->value);
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->can(PermissionName::ViewUsers->value);
    }

    public function invite(User $actor): bool
    {
        return $actor->can(PermissionName::InviteUsers->value);
    }

    public function manageStatus(User $actor, User $target): bool
    {
        return $actor->can(PermissionName::ManageUserStatus->value)
            && ! $actor->is($target)
            && ! $target->hasRole(RoleName::SuperAdmin->value);
    }

    public function manageSecurity(User $actor, User $target): bool
    {
        return $actor->can(PermissionName::ManageUserSecurity->value)
            && ! $actor->is($target)
            && ! $target->hasRole(RoleName::SuperAdmin->value);
    }

    public function revokeSessions(User $actor, User $target): bool
    {
        return $this->manageSecurity($actor, $target);
    }

    public function sendPasswordReset(User $actor, User $target): bool
    {
        return $this->manageSecurity($actor, $target);
    }

    public function resetTwoFactor(User $actor, User $target): bool
    {
        return $this->manageSecurity($actor, $target);
    }

    public function synchronizeRoles(User $actor, User $target): bool
    {
        return $actor->isInternalAccount()
            && $target->isInternalAccount()
            && ! $actor->is($target)
            && $actor->can(PermissionName::ManageAuthorization->value);
    }
}

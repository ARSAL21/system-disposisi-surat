<?php

namespace App\Policies;

use App\Authorization\AuthorizationCatalog;
use App\Enums\PermissionName;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInternalAccount()
            && $user->can(PermissionName::ViewAuthorization->value);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->canMutateRole($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->canMutateRole($user, $role);
    }

    public function synchronizePermissions(User $user, Role $role): bool
    {
        return $this->canMutateRole($user, $role);
    }

    private function canManage(User $user): bool
    {
        return $user->isInternalAccount()
            && $user->can(PermissionName::ManageAuthorization->value);
    }

    private function canMutateRole(User $user, Role $role): bool
    {
        if (! $this->canManage($user)
            || $role->guard_name !== AuthorizationCatalog::GUARD_NAME
            || AuthorizationCatalog::isProtectedRole($role->name)) {
            return false;
        }

        return ! $user->roles()
            ->whereKey($role->getKey())
            ->exists();
    }
}

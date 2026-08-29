<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\OrganizationalUnit;
use App\Models\User;

class OrganizationalUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasCapability($user, PermissionName::ViewOrganization);
    }

    public function view(User $user, OrganizationalUnit $organizationalUnit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasCapability($user, PermissionName::ManageOrganization);
    }

    public function update(User $user, OrganizationalUnit $organizationalUnit): bool
    {
        return $this->create($user);
    }

    public function changeStatus(User $user, OrganizationalUnit $organizationalUnit): bool
    {
        return $this->create($user);
    }

    private function hasCapability(User $user, PermissionName $permission): bool
    {
        return $user->isInternalAccount()
            && $user->is_active
            && $user->hasVerifiedEmail()
            && $user->can($permission->value);
    }
}

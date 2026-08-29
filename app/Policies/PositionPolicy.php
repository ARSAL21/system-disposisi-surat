<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasCapability($user, PermissionName::ViewOrganization);
    }

    public function view(User $user, Position $position): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasCapability($user, PermissionName::ManageOrganization);
    }

    public function update(User $user, Position $position): bool
    {
        return $this->create($user);
    }

    public function changeStatus(User $user, Position $position): bool
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

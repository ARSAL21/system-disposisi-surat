<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

class UserPolicy
{
    public function synchronizeRoles(User $actor, User $target): bool
    {
        return $actor->isInternalAccount()
            && $target->isInternalAccount()
            && ! $actor->is($target)
            && $actor->can(PermissionName::ManageAuthorization->value);
    }
}

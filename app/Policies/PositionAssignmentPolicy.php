<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\PositionAssignment;
use App\Models\User;

class PositionAssignmentPolicy
{
    public function assign(User $user): bool
    {
        return $this->canManagePositionAssignments($user);
    }

    public function replace(User $user): bool
    {
        return $this->canManagePositionAssignments($user);
    }

    public function end(User $user, PositionAssignment $assignment): bool
    {
        return $this->canManagePositionAssignments($user);
    }

    private function canManagePositionAssignments(User $user): bool
    {
        return $user->isInternalAccount()
            && $user->is_active
            && $user->hasVerifiedEmail()
            && $user->can(PermissionName::ManagePositionAssignments->value);
    }
}

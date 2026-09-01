<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\InstructionLabel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstructionLabelPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->authorize($user, PermissionName::ViewDispositionInstructions);
    }

    public function create(User $user): Response
    {
        return $this->authorize($user, PermissionName::ManageDispositionInstructions);
    }

    public function update(User $user, InstructionLabel $instructionLabel): Response
    {
        return $this->authorize($user, PermissionName::ManageDispositionInstructions);
    }

    private function authorize(User $user, PermissionName $permission): Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission->value)
            ? Response::allow()
            : Response::deny('You do not have permission to access disposition instruction labels.');
    }
}

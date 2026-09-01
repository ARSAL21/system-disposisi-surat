<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Disposition;
use App\Models\User;
use App\Services\DispositionPositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

class DispositionPolicy
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
    ) {}

    public function view(User $user, Disposition $disposition): Response
    {
        if (! $this->isEligibleInternalUser($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::ViewExecutiveInbox->value)) {
            return Response::deny('You do not have permission to view this disposition.');
        }

        $positionId = $disposition->sourceRoute()->value('recipient_position_id');

        return is_numeric($positionId)
            && $this->positionAssignmentResolver->hasExecutiveAssignmentForPosition($user, (int) $positionId)
                ? Response::allow()
                : Response::denyAsNotFound();
    }

    private function isEligibleInternalUser(User $user): bool
    {
        return $user->isInternalAccount() && $user->is_active && $user->hasVerifiedEmail();
    }
}

<?php

namespace App\Policies;

use App\Enums\LetterRouteStatus;
use App\Enums\PermissionName;
use App\Models\LetterRoute;
use App\Models\User;
use App\Services\LetterRoutingPositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

class LetterRoutePolicy
{
    public function __construct(
        private readonly LetterRoutingPositionAssignmentResolver $positionAssignmentResolver,
    ) {}

    public function viewAnyInbox(User $user): Response
    {
        return $this->authorizeInboxViewing($user);
    }

    public function viewInbox(User $user, LetterRoute $letterRoute): Response
    {
        $authorization = $this->authorizeInboxViewing($user);

        if ($authorization->denied()) {
            return $authorization;
        }

        return $letterRoute->status === LetterRouteStatus::Pending
            && $this->positionAssignmentResolver->hasExecutiveAssignmentForPosition(
                $user,
                $letterRoute->recipient_position_id,
            )
                ? Response::allow()
                : Response::denyAsNotFound();
    }

    private function authorizeInboxViewing(User $user): Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::ViewExecutiveInbox->value)) {
            return Response::deny('You do not have permission to view the executive inbox.');
        }

        return $this->positionAssignmentResolver->hasExecutiveInboxAssignment($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

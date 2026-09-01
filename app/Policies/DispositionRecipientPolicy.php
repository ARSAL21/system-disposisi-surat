<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\DispositionRecipient;
use App\Models\User;
use App\Services\DispositionPositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

class DispositionRecipientPolicy
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
    ) {}

    public function viewAnyInbox(User $user): Response
    {
        return $this->authorizeInboxViewing($user);
    }

    public function viewInbox(User $user, DispositionRecipient $recipient): Response
    {
        $authorization = $this->authorizeInboxViewing($user);

        if ($authorization->denied()) {
            return $authorization;
        }

        return $this->positionAssignmentResolver->hasInboxAssignmentForPosition(
            $user,
            $recipient->recipient_position_id,
        )
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function forwardDisposition(User $user, DispositionRecipient $recipient): Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::CreateDispositions->value)) {
            return Response::deny('You do not have permission to create dispositions.');
        }

        return $this->positionAssignmentResolver->hasAssistantAssignmentForPosition(
            $user,
            $recipient->recipient_position_id,
        )
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function authorizeInboxViewing(User $user): Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::ViewDispositions->value)) {
            return Response::deny('You do not have permission to view disposition inboxes.');
        }

        return $this->positionAssignmentResolver->hasInboxAssignment($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

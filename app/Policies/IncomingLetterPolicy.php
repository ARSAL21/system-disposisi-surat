<?php

namespace App\Policies;

use App\Enums\IncomingLetterStatus;
use App\Enums\PermissionName;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Services\DocumentVersionPositionAssignmentResolver;
use App\Services\LetterRoutingPositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

class IncomingLetterPolicy
{
    public function __construct(
        private readonly DocumentVersionPositionAssignmentResolver $positionAssignmentResolver,
        private readonly LetterRoutingPositionAssignmentResolver $routingPositionAssignmentResolver,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->authorizeViewing($user);
    }

    public function view(User $user, IncomingLetter $incomingLetter): Response
    {
        return $this->authorizeViewing($user);
    }

    public function viewDocumentVersions(User $user, IncomingLetter $incomingLetter): Response
    {
        return $this->authorizeViewing($user);
    }

    public function createDocumentVersion(User $user, IncomingLetter $incomingLetter): Response
    {
        if (! $this->isEligibleInternalUser($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::CreateDocumentVersions->value)) {
            return Response::deny('You do not have permission to create document versions.');
        }

        return $this->positionAssignmentResolver->hasCreatingAssignment($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function viewAnyRouting(User $user): Response
    {
        return $this->authorizeRoutingViewing($user);
    }

    public function viewRouting(User $user, IncomingLetter $incomingLetter): Response
    {
        $authorization = $this->authorizeRoutingViewing($user);

        if ($authorization->denied()) {
            return $authorization;
        }

        return in_array($incomingLetter->status, [
            IncomingLetterStatus::Registered,
            IncomingLetterStatus::Routed,
        ], true)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function createRoute(User $user, IncomingLetter $incomingLetter): Response
    {
        if (! $this->isEligibleInternalUser($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::CreateLetterRouting->value)) {
            return Response::deny('You do not have permission to route incoming letters.');
        }

        if (! $this->routingPositionAssignmentResolver->hasRoutingCreatingAssignment($user)) {
            return Response::denyAsNotFound();
        }

        return $incomingLetter->status === IncomingLetterStatus::Registered
            && ! $incomingLetter->routes()->exists()
                ? Response::allow()
                : Response::denyAsNotFound();
    }

    private function authorizeViewing(User $user): Response
    {
        if (! $this->isEligibleInternalUser($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::ViewDocumentVersions->value)) {
            return Response::deny('You do not have permission to view document versions.');
        }

        return $this->positionAssignmentResolver->hasViewingAssignment($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function authorizeRoutingViewing(User $user): Response
    {
        if (! $this->isEligibleInternalUser($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::ViewLetterRouting->value)) {
            return Response::deny('You do not have permission to view incoming letter routing.');
        }

        return $this->routingPositionAssignmentResolver->hasRoutingViewingAssignment($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function isEligibleInternalUser(User $user): bool
    {
        return $user->isInternalAccount()
            && $user->is_active
            && $user->hasVerifiedEmail();
    }
}

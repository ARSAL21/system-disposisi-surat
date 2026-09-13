<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\ExpertConsultations\ExpertConsultationPositionResolver;
use App\Models\ExpertConsultation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class ExpertConsultationPolicy
{
    public function __construct(private readonly ExpertConsultationPositionResolver $positionResolver) {}

    public function view(User $user, ExpertConsultation $consultation): Response
    {
        if (! $this->validInternal($user) || ! $user->can(PermissionName::ViewExpertConsultations->value)) {
            return $user->can(PermissionName::ViewExpertConsultations->value) ? Response::denyAsNotFound() : Response::deny('You do not have permission to view expert consultations.');
        }
        if ($this->positionResolver->hasExpertAssignmentForPosition($user, $consultation->expert_position_id)
            || $this->positionResolver->hasCoordinationAssignment($user)) {
            return Response::allow();
        }

        return $this->positionResolver->hasMayorAssignmentForPosition($user, $consultation->letterRoute->recipient_position_id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function respond(User $user, ExpertConsultation $consultation): Response
    {
        if (! $this->validInternal($user)) {
            return Response::denyAsNotFound();
        }
        if (! $user->can(PermissionName::RespondExpertConsultations->value)) {
            return Response::deny('You do not have permission to respond to expert consultations.');
        }

        return $this->positionResolver->hasExpertAssignmentForPosition($user, $consultation->expert_position_id)
            ? Response::allow() : Response::denyAsNotFound();
    }

    public function cancel(User $user, ExpertConsultation $consultation): Response
    {
        if (! $this->validInternal($user)) {
            return Response::denyAsNotFound();
        }
        if (! $user->can(PermissionName::RequestExpertConsultations->value)) {
            return Response::deny('You do not have permission to cancel expert consultations.');
        }

        return $this->positionResolver->hasMayorAssignmentForPosition($user, $consultation->letterRoute->recipient_position_id)
            ? Response::allow() : Response::denyAsNotFound();
    }

    public function coordinate(User $user): Response
    {
        if (! $this->validInternal($user)) {
            return Response::denyAsNotFound();
        }
        if (! $user->can(PermissionName::CoordinateExpertConsultations->value)) {
            return Response::deny('You do not have permission to coordinate expert consultations.');
        }

        return $this->positionResolver->hasCoordinationAssignment($user) ? Response::allow() : Response::denyAsNotFound();
    }

    private function validInternal(User $user): bool
    {
        return $user->isInternalAccount() && $user->is_active && $user->hasVerifiedEmail();
    }
}

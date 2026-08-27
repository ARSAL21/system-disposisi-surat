<?php

namespace App\Services;

use App\Exceptions\PositionAssignmentNotAllowed;
use App\Models\Position;
use App\Models\User;

class PositionAssignmentEligibility
{
    public function ensureEligibleActor(User $actor): void
    {
        if (! $actor->isInternalAccount() || ! $actor->is_active || ! $actor->hasVerifiedEmail()) {
            throw PositionAssignmentNotAllowed::ineligibleActor();
        }
    }

    public function ensureEligibleAssignee(User $assignee): void
    {
        if (! $assignee->isInternalAccount() || ! $assignee->is_active) {
            throw PositionAssignmentNotAllowed::ineligibleAssignee();
        }
    }

    public function ensureAssignablePosition(Position $position): void
    {
        if (! $position->is_active) {
            throw PositionAssignmentNotAllowed::inactivePosition($position);
        }
    }
}

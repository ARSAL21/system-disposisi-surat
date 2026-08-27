<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\PositionAssignmentConflict;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\PositionAssignmentEligibility;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class AssignUserToPosition
{
    public function __construct(
        private readonly PositionAssignmentEligibility $eligibility,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, User $assignee, Position $position): PositionAssignment
    {
        return DB::transaction(function () use ($actor, $assignee, $position): PositionAssignment {
            $lockedPosition = Position::query()
                ->whereKey($position->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedActor = User::query()
                ->whereKey($actor->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAssignee = User::query()
                ->whereKey($assignee->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureEligibleActor($lockedActor);
            $this->eligibility->ensureEligibleAssignee($lockedAssignee);
            $this->eligibility->ensureAssignablePosition($lockedPosition);

            $activeAssignmentExists = PositionAssignment::query()
                ->active()
                ->where('position_id', $lockedPosition->getKey())
                ->lockForUpdate()
                ->exists();

            if ($activeAssignmentExists) {
                throw PositionAssignmentConflict::positionOccupied($lockedPosition);
            }

            $startedAt = Date::now();

            $assignment = new PositionAssignment;
            $assignment->user_id = $lockedAssignee->getKey();
            $assignment->position_id = $lockedPosition->getKey();
            $assignment->started_at = $startedAt;
            $assignment->ended_at = null;
            $assignment->assigned_by_user_id = $lockedActor->getKey();
            $assignment->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::PositionAssigned,
                subjectType: 'position',
                subjectId: $lockedPosition->getKey(),
                newValues: [
                    'assignment_id' => $assignment->getKey(),
                    'user_id' => $lockedAssignee->getKey(),
                    'started_at' => $startedAt->toISOString(),
                ],
            );

            return $assignment;
        }, attempts: 3);
    }
}

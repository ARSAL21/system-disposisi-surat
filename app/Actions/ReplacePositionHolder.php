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

class ReplacePositionHolder
{
    public function __construct(
        private readonly PositionAssignmentEligibility $eligibility,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, User $newHolder, Position $position): PositionAssignment
    {
        return DB::transaction(function () use ($actor, $newHolder, $position): PositionAssignment {
            $lockedPosition = Position::query()
                ->whereKey($position->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedActor = User::query()
                ->whereKey($actor->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedNewHolder = User::query()
                ->whereKey($newHolder->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureEligibleActor($lockedActor);
            $this->eligibility->ensureEligibleAssignee($lockedNewHolder);
            $this->eligibility->ensureAssignablePosition($lockedPosition);

            $currentAssignment = PositionAssignment::query()
                ->active()
                ->where('position_id', $lockedPosition->getKey())
                ->lockForUpdate()
                ->first();

            if ($currentAssignment === null) {
                throw PositionAssignmentConflict::positionVacant($lockedPosition);
            }

            if ($currentAssignment->user_id === $lockedNewHolder->getKey()) {
                throw PositionAssignmentConflict::sameHolder($lockedPosition);
            }

            $effectiveAt = Date::now();

            if (! $effectiveAt->greaterThan($currentAssignment->started_at)) {
                throw PositionAssignmentConflict::invalidEffectiveTime();
            }

            $currentAssignment->ended_at = $effectiveAt;
            $currentAssignment->save();

            $newAssignment = new PositionAssignment;
            $newAssignment->user_id = $lockedNewHolder->getKey();
            $newAssignment->position_id = $lockedPosition->getKey();
            $newAssignment->started_at = $effectiveAt;
            $newAssignment->ended_at = null;
            $newAssignment->assigned_by_user_id = $lockedActor->getKey();
            $newAssignment->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::PositionHolderReplaced,
                subjectType: 'position',
                subjectId: $lockedPosition->getKey(),
                oldValues: [
                    'assignment_id' => $currentAssignment->getKey(),
                    'user_id' => $currentAssignment->user_id,
                ],
                newValues: [
                    'assignment_id' => $newAssignment->getKey(),
                    'user_id' => $lockedNewHolder->getKey(),
                    'started_at' => $effectiveAt->toISOString(),
                ],
            );

            return $newAssignment;
        }, attempts: 3);
    }
}

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

class EndPositionAssignment
{
    public function __construct(
        private readonly PositionAssignmentEligibility $eligibility,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, PositionAssignment $assignment): PositionAssignment
    {
        return DB::transaction(function () use ($actor, $assignment): PositionAssignment {
            $lockedPosition = Position::query()
                ->whereKey($assignment->position_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedActor = User::query()
                ->whereKey($actor->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAssignment = PositionAssignment::query()
                ->whereKey($assignment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureEligibleActor($lockedActor);

            if (! $lockedAssignment->isActive()) {
                throw PositionAssignmentConflict::alreadyEnded($lockedAssignment);
            }

            $endedAt = Date::now();

            if (! $endedAt->greaterThan($lockedAssignment->started_at)) {
                throw PositionAssignmentConflict::invalidEffectiveTime();
            }

            $lockedAssignment->ended_at = $endedAt;
            $lockedAssignment->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::PositionAssignmentEnded,
                subjectType: 'position',
                subjectId: $lockedPosition->getKey(),
                oldValues: [
                    'assignment_id' => $lockedAssignment->getKey(),
                    'user_id' => $lockedAssignment->user_id,
                    'ended_at' => null,
                ],
                newValues: [
                    'assignment_id' => $lockedAssignment->getKey(),
                    'user_id' => $lockedAssignment->user_id,
                    'ended_at' => $endedAt->toISOString(),
                ],
            );

            return $lockedAssignment;
        }, attempts: 3);
    }
}

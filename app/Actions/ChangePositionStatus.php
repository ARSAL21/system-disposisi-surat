<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\OrganizationConflict;
use App\Exceptions\OrganizationNotAllowed;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Support\Facades\DB;

class ChangePositionStatus
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    public function execute(User $actor, Position $position, bool $isActive): Position
    {
        return DB::transaction(function () use ($actor, $position, $isActive): Position {
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $lockedPosition = Position::query()->whereKey($position->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedPosition->is_active === $isActive) {
                return $lockedPosition;
            }

            if ($isActive) {
                $level = PositionLevel::query()->whereKey($lockedPosition->position_level_id)->lockForUpdate()->firstOrFail();
                $unit = $lockedPosition->organizational_unit_id === null
                    ? null
                    : OrganizationalUnit::query()->whereKey($lockedPosition->organizational_unit_id)->lockForUpdate()->firstOrFail();

                if (! OrganizationCatalog::isProtectedPositionLevel($level->code)) {
                    throw OrganizationNotAllowed::unprotectedPositionLevel();
                }

                if (! $level->is_active || ($unit !== null && ! $unit->is_active)) {
                    throw OrganizationNotAllowed::inactivePositionDependency();
                }
            } else {
                $hasActiveAssignment = PositionAssignment::query()
                    ->active()
                    ->where('position_id', $lockedPosition->getKey())
                    ->lockForUpdate()
                    ->exists();

                if ($hasActiveAssignment) {
                    throw OrganizationConflict::activeDependencies(
                        "Jabatan [{$lockedPosition->code}]",
                        'pejabat aktif',
                    );
                }
            }

            $before = ['is_active' => $lockedPosition->is_active];
            $lockedPosition->is_active = $isActive;
            $lockedPosition->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::PositionStatusChanged,
                subjectType: 'position',
                subjectId: $lockedPosition->getKey(),
                oldValues: $before,
                newValues: ['is_active' => $lockedPosition->is_active],
            );

            return $lockedPosition;
        }, attempts: 3);
    }
}

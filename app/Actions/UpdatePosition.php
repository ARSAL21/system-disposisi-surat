<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\OrganizationNotAllowed;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePosition
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /** @param array{organizational_unit_id: int|null, name: string} $attributes */
    public function execute(User $actor, Position $position, array $attributes): Position
    {
        return DB::transaction(function () use ($actor, $position, $attributes): Position {
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $lockedPosition = Position::query()->whereKey($position->getKey())->lockForUpdate()->firstOrFail();
            $unit = $attributes['organizational_unit_id'] === null
                ? null
                : OrganizationalUnit::query()->whereKey($attributes['organizational_unit_id'])->lockForUpdate()->firstOrFail();

            if ($unit !== null && ! $unit->is_active) {
                throw OrganizationNotAllowed::inactivePositionDependency();
            }

            $before = $this->snapshot($lockedPosition);
            $lockedPosition->organizational_unit_id = $unit?->getKey();
            $lockedPosition->name = $attributes['name'];

            if (! $lockedPosition->isDirty()) {
                return $lockedPosition;
            }

            $lockedPosition->save();
            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::PositionUpdated,
                subjectType: 'position',
                subjectId: $lockedPosition->getKey(),
                oldValues: $before,
                newValues: $this->snapshot($lockedPosition),
            );

            return $lockedPosition;
        }, attempts: 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(Position $position): array
    {
        return ['organizational_unit_id' => $position->organizational_unit_id, 'name' => $position->name];
    }
}

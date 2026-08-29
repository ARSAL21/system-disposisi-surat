<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\OrganizationNotAllowed;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionLevel;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Support\Facades\DB;

class CreatePosition
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /** @param array{position_level_id: int, organizational_unit_id: int|null, code: string, name: string} $attributes */
    public function execute(User $actor, array $attributes): Position
    {
        return DB::transaction(function () use ($actor, $attributes): Position {
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $level = PositionLevel::query()->whereKey($attributes['position_level_id'])->lockForUpdate()->firstOrFail();
            $unit = $attributes['organizational_unit_id'] === null
                ? null
                : OrganizationalUnit::query()->whereKey($attributes['organizational_unit_id'])->lockForUpdate()->firstOrFail();

            $this->ensureDependenciesAreValid($level, $unit);

            $position = new Position;
            $position->position_level_id = $level->getKey();
            $position->organizational_unit_id = $unit?->getKey();
            $position->code = $attributes['code'];
            $position->name = $attributes['name'];
            $position->is_active = true;
            $position->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::PositionCreated,
                subjectType: 'position',
                subjectId: $position->getKey(),
                newValues: $this->snapshot($position),
            );

            return $position;
        }, attempts: 3);
    }

    private function ensureDependenciesAreValid(PositionLevel $level, ?OrganizationalUnit $unit): void
    {
        if (! OrganizationCatalog::isProtectedPositionLevel($level->code)) {
            throw OrganizationNotAllowed::unprotectedPositionLevel();
        }

        if (! $level->is_active || ($unit !== null && ! $unit->is_active)) {
            throw OrganizationNotAllowed::inactivePositionDependency();
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Position $position): array
    {
        return [
            'position_level_id' => $position->position_level_id,
            'organizational_unit_id' => $position->organizational_unit_id,
            'code' => $position->code,
            'name' => $position->name,
            'is_active' => $position->is_active,
        ];
    }
}

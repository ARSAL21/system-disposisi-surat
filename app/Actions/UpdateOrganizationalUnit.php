<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\OrganizationHierarchy;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationalUnit
{
    public function __construct(
        private readonly OrganizationHierarchy $hierarchy,
        private readonly RecordAudit $recordAudit,
    ) {}

    /** @param array{parent_id: int|null, name: string} $attributes */
    public function execute(User $actor, OrganizationalUnit $unit, array $attributes): OrganizationalUnit
    {
        return DB::transaction(function () use ($actor, $unit, $attributes): OrganizationalUnit {
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $lockedUnit = OrganizationalUnit::query()->whereKey($unit->getKey())->lockForUpdate()->firstOrFail();
            $parent = $attributes['parent_id'] === null
                ? null
                : OrganizationalUnit::query()->whereKey($attributes['parent_id'])->lockForUpdate()->firstOrFail();

            $this->hierarchy->ensureValidParent($parent, $lockedUnit);
            $before = $this->snapshot($lockedUnit);
            $lockedUnit->parent_id = $parent?->getKey();
            $lockedUnit->name = $attributes['name'];

            if (! $lockedUnit->isDirty()) {
                return $lockedUnit;
            }

            $lockedUnit->save();
            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::OrganizationalUnitUpdated,
                subjectType: 'organizational_unit',
                subjectId: $lockedUnit->getKey(),
                oldValues: $before,
                newValues: $this->snapshot($lockedUnit),
            );

            return $lockedUnit;
        }, attempts: 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(OrganizationalUnit $unit): array
    {
        return ['parent_id' => $unit->parent_id, 'code' => $unit->code, 'name' => $unit->name];
    }
}

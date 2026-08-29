<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\OrganizationHierarchy;
use Illuminate\Support\Facades\DB;

class CreateOrganizationalUnit
{
    public function __construct(
        private readonly OrganizationHierarchy $hierarchy,
        private readonly RecordAudit $recordAudit,
    ) {}

    /** @param array{parent_id: int|null, code: string|null, name: string} $attributes */
    public function execute(User $actor, array $attributes): OrganizationalUnit
    {
        return DB::transaction(function () use ($actor, $attributes): OrganizationalUnit {
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $parent = $attributes['parent_id'] === null
                ? null
                : OrganizationalUnit::query()->whereKey($attributes['parent_id'])->lockForUpdate()->firstOrFail();

            $this->hierarchy->ensureValidParent($parent);

            $unit = new OrganizationalUnit;
            $unit->parent_id = $parent?->getKey();
            $unit->code = $attributes['code'];
            $unit->name = $attributes['name'];
            $unit->is_active = true;
            $unit->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::OrganizationalUnitCreated,
                subjectType: 'organizational_unit',
                subjectId: $unit->getKey(),
                newValues: $this->snapshot($unit),
            );

            return $unit;
        }, attempts: 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(OrganizationalUnit $unit): array
    {
        return [
            'parent_id' => $unit->parent_id,
            'code' => $unit->code,
            'name' => $unit->name,
            'is_active' => $unit->is_active,
        ];
    }
}

<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\OrganizationConflict;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\User;
use App\Services\OrganizationHierarchy;
use Illuminate\Support\Facades\DB;

class ChangeOrganizationalUnitStatus
{
    public function __construct(
        private readonly OrganizationHierarchy $hierarchy,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, OrganizationalUnit $unit, bool $isActive): OrganizationalUnit
    {
        return DB::transaction(function () use ($actor, $unit, $isActive): OrganizationalUnit {
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $lockedUnit = OrganizationalUnit::query()->whereKey($unit->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedUnit->is_active === $isActive) {
                return $lockedUnit;
            }

            if ($isActive) {
                $parent = $lockedUnit->parent_id === null
                    ? null
                    : OrganizationalUnit::query()->whereKey($lockedUnit->parent_id)->lockForUpdate()->firstOrFail();
                $this->hierarchy->ensureValidParent($parent, $lockedUnit);
            } else {
                $hasActiveChildren = OrganizationalUnit::query()
                    ->where('parent_id', $lockedUnit->getKey())
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->exists();
                $hasActivePositions = Position::query()
                    ->where('organizational_unit_id', $lockedUnit->getKey())
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->exists();

                if ($hasActiveChildren || $hasActivePositions) {
                    throw OrganizationConflict::activeDependencies(
                        "Unit [{$lockedUnit->name}]",
                        $hasActiveChildren ? 'unit turunan' : 'jabatan',
                    );
                }
            }

            $before = ['is_active' => $lockedUnit->is_active];
            $lockedUnit->is_active = $isActive;
            $lockedUnit->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::OrganizationalUnitStatusChanged,
                subjectType: 'organizational_unit',
                subjectId: $lockedUnit->getKey(),
                oldValues: $before,
                newValues: ['is_active' => $lockedUnit->is_active],
            );

            return $lockedUnit;
        }, attempts: 3);
    }
}

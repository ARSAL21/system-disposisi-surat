<?php

namespace App\Actions;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SynchronizeRolePermissions
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @param  list<string>  $permissionNames
     * @return array{role: Role, changed: bool}
     */
    public function execute(User $actor, Role $role, array $permissionNames): array
    {
        $operationId = Str::uuid()->toString();
        sort($permissionNames);

        try {
            return DB::transaction(function () use ($actor, $role, $permissionNames, $operationId): array {
                $lockedRole = Role::query()
                    ->whereKey($role->getKey())
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::forUser($actor)->authorize('synchronizePermissions', $lockedRole);

                $permissions = Permission::query()
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->whereIn('name', $permissionNames)
                    ->orderBy('name')
                    ->lockForUpdate()
                    ->get();

                $currentPermissions = $lockedRole->permissions()
                    ->pluck('name')
                    ->map(static fn (mixed $name): string => (string) $name)
                    ->sort()
                    ->values()
                    ->all();

                if ($currentPermissions === $permissionNames) {
                    return ['role' => $lockedRole, 'changed' => false];
                }

                $lockedRole->syncPermissions($permissions);

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::PermissionChanged,
                    subjectType: 'role',
                    subjectId: $lockedRole->getKey(),
                    oldValues: ['permissions' => $currentPermissions],
                    newValues: ['permissions' => $permissionNames],
                    metadata: ['source' => 'web', 'change' => 'role_permissions_synchronized'],
                    requestId: $operationId,
                );

                return ['role' => $lockedRole, 'changed' => true];
            }, attempts: 3);
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }
}

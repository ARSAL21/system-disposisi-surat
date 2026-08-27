<?php

namespace App\Actions;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\RoleName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SynchronizeAuthorizationCatalog
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @return array{
     *     created_permissions: list<string>,
     *     created_roles: list<string>,
     *     changed_roles: list<string>,
     *     unknown_permissions: list<string>,
     *     unknown_roles: list<string>
     * }
     */
    public function execute(): array
    {
        $operationId = Str::uuid()->toString();

        try {
            return DB::transaction(
                fn (): array => $this->synchronize($operationId),
                attempts: 3,
            );
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }

    /**
     * @return array{
     *     created_permissions: list<string>,
     *     created_roles: list<string>,
     *     changed_roles: list<string>,
     *     unknown_permissions: list<string>,
     *     unknown_roles: list<string>
     * }
     */
    private function synchronize(string $operationId): array
    {
        Permission::query()->lockForUpdate()->get();
        Role::query()->lockForUpdate()->get();

        $createdPermissions = [];
        $createdRoles = [];
        $changedRoles = [];

        foreach (AuthorizationCatalog::permissionNames() as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, AuthorizationCatalog::GUARD_NAME);

            if (! $permission->wasRecentlyCreated) {
                continue;
            }

            $createdPermissions[] = $permissionName;
            $this->recordAudit->execute(
                actor: null,
                action: AuditAction::PermissionChanged,
                subjectType: 'permission',
                subjectId: $permission->getKey(),
                newValues: [
                    'name' => $permissionName,
                    'guard_name' => AuthorizationCatalog::GUARD_NAME,
                ],
                metadata: $this->consoleMetadata('permission_created'),
                requestId: $operationId,
            );
        }

        foreach (RoleName::cases() as $roleName) {
            $role = Role::findOrCreate($roleName->value, AuthorizationCatalog::GUARD_NAME);

            if ($role->wasRecentlyCreated) {
                $createdRoles[] = $roleName->value;
                $this->recordAudit->execute(
                    actor: null,
                    action: AuditAction::RoleChanged,
                    subjectType: 'role',
                    subjectId: $role->getKey(),
                    newValues: [
                        'name' => $roleName->value,
                        'guard_name' => AuthorizationCatalog::GUARD_NAME,
                    ],
                    metadata: $this->consoleMetadata('role_created'),
                    requestId: $operationId,
                );
            }

            $currentPermissions = $role->permissions()
                ->pluck('name')
                ->map(static fn (mixed $name): string => (string) $name)
                ->sort()
                ->values()
                ->all();
            $catalogPermissions = AuthorizationCatalog::permissionsFor($roleName);
            sort($catalogPermissions);

            if ($currentPermissions === $catalogPermissions) {
                continue;
            }

            $role->syncPermissions($catalogPermissions);
            $changedRoles[] = $roleName->value;

            $this->recordAudit->execute(
                actor: null,
                action: AuditAction::PermissionChanged,
                subjectType: 'role',
                subjectId: $role->getKey(),
                oldValues: ['permissions' => $currentPermissions],
                newValues: ['permissions' => $catalogPermissions],
                metadata: $this->consoleMetadata('role_permissions_synchronized'),
                requestId: $operationId,
            );
        }

        return [
            'created_permissions' => $createdPermissions,
            'created_roles' => $createdRoles,
            'changed_roles' => $changedRoles,
            'unknown_permissions' => $this->unknownPermissionNames(),
            'unknown_roles' => $this->unknownRoleNames(),
        ];
    }

    /** @return list<string> */
    private function unknownPermissionNames(): array
    {
        $permissionNames = Permission::query()
            ->get(['name', 'guard_name'])
            ->filter(fn (Permission $permission): bool => $permission->guard_name !== AuthorizationCatalog::GUARD_NAME
                || ! in_array($permission->name, AuthorizationCatalog::permissionNames(), true))
            ->map(fn (Permission $permission): string => "{$permission->guard_name}:{$permission->name}")
            ->sort()
            ->values()
            ->all();

        return array_values($permissionNames);
    }

    /** @return list<string> */
    private function unknownRoleNames(): array
    {
        $roleNames = Role::query()
            ->get(['name', 'guard_name'])
            ->filter(fn (Role $role): bool => $role->guard_name !== AuthorizationCatalog::GUARD_NAME
                || ! in_array($role->name, AuthorizationCatalog::roleNames(), true))
            ->map(fn (Role $role): string => "{$role->guard_name}:{$role->name}")
            ->sort()
            ->values()
            ->all();

        return array_values($roleNames);
    }

    /** @return array{source: string, command: string, change: string} */
    private function consoleMetadata(string $change): array
    {
        return [
            'source' => 'console',
            'command' => 'authorization:sync',
            'change' => $change,
        ];
    }
}

<?php

namespace App\Actions;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DeleteAuthorizationRole
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function execute(User $actor, Role $role): void
    {
        $operationId = Str::uuid()->toString();

        try {
            DB::transaction(function () use ($actor, $role, $operationId): void {
                $lockedRole = Role::query()
                    ->whereKey($role->getKey())
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::forUser($actor)->authorize('delete', $lockedRole);

                $pivotRoleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
                $assignment = DB::table(config('permission.table_names.model_has_roles'))
                    ->where($pivotRoleKey, $lockedRole->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($assignment !== null) {
                    throw new ConflictHttpException('Role masih digunakan dan tidak dapat dihapus.');
                }

                $permissions = $lockedRole->permissions()
                    ->pluck('name')
                    ->map(static fn (mixed $name): string => (string) $name)
                    ->sort()
                    ->values()
                    ->all();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::RoleChanged,
                    subjectType: 'role',
                    subjectId: $lockedRole->getKey(),
                    oldValues: [
                        'name' => $lockedRole->name,
                        'guard_name' => $lockedRole->guard_name,
                        'permissions' => $permissions,
                    ],
                    metadata: ['source' => 'web', 'change' => 'role_deleted'],
                    requestId: $operationId,
                );

                $lockedRole->delete();
            }, attempts: 3);
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }
}

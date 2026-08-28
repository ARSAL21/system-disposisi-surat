<?php

namespace App\Actions;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SynchronizeUserRoles
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @param  list<int>  $requestedRoleIds
     * @return array{user: User, changed: bool}
     */
    public function execute(User $actor, User $target, array $requestedRoleIds): array
    {
        $operationId = Str::uuid()->toString();
        $requestedRoleIds = array_values(array_unique(array_map('intval', $requestedRoleIds)));
        sort($requestedRoleIds);

        try {
            return DB::transaction(function () use ($actor, $target, $requestedRoleIds, $operationId): array {
                $lockedTarget = User::query()
                    ->whereKey($target->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::forUser($actor)->authorize('synchronizeRoles', $lockedTarget);

                $requestedRoles = Role::query()
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->whereIn('id', $requestedRoleIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($requestedRoles->contains(
                    fn (Role $role): bool => AuthorizationCatalog::isProtectedRole($role->name),
                )) {
                    throw ValidationException::withMessages([
                        'role_ids' => 'Protected role tidak dapat dikelola melalui web.',
                    ]);
                }

                $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
                $modelKey = config('permission.column_names.model_morph_key') ?: 'model_id';
                $currentRoleIds = DB::table(config('permission.table_names.model_has_roles'))
                    ->where('model_type', $lockedTarget->getMorphClass())
                    ->where($modelKey, $lockedTarget->getKey())
                    ->lockForUpdate()
                    ->pluck($rolePivotKey)
                    ->map(static fn (mixed $roleId): int => (int) $roleId)
                    ->all();
                $currentRoles = Role::query()
                    ->whereIn('id', $currentRoleIds)
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $currentCustomRoles = $currentRoles->reject(
                    fn (Role $role): bool => AuthorizationCatalog::isProtectedRole($role->name),
                );
                $protectedRoles = $currentRoles->filter(
                    fn (Role $role): bool => AuthorizationCatalog::isProtectedRole($role->name),
                );

                $addedRoleIds = array_diff($requestedRoleIds, $currentCustomRoles->modelKeys());

                if ($addedRoleIds !== [] && (! $lockedTarget->is_active || ! $lockedTarget->hasVerifiedEmail())) {
                    throw ValidationException::withMessages([
                        'role_ids' => 'Role baru hanya dapat diberikan kepada akun internal yang aktif dan terverifikasi.',
                    ]);
                }

                $oldRoleNames = $this->roleNames($currentRoles);
                $newRoleIds = array_values(array_unique([
                    ...$protectedRoles->modelKeys(),
                    ...$requestedRoles->modelKeys(),
                ]));
                $newRoles = Role::query()
                    ->whereIn('id', $newRoleIds)
                    ->orderBy('id')
                    ->get();
                $newRoleNames = $this->roleNames($newRoles);

                if ($oldRoleNames === $newRoleNames) {
                    return ['user' => $lockedTarget, 'changed' => false];
                }

                $lockedTarget->syncRoles($newRoles);

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::RoleChanged,
                    subjectType: 'user',
                    subjectId: $lockedTarget->getKey(),
                    oldValues: ['roles' => $oldRoleNames],
                    newValues: ['roles' => $newRoleNames],
                    metadata: ['source' => 'web', 'change' => 'user_roles_synchronized'],
                    requestId: $operationId,
                );

                return ['user' => $lockedTarget, 'changed' => true];
            }, attempts: 3);
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @return list<string>
     */
    private function roleNames(Collection $roles): array
    {
        return array_values($roles
            ->pluck('name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->sort()
            ->values()
            ->all());
    }
}

<?php

namespace App\Actions;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\RoleName;
use App\Exceptions\PrivilegeAssignmentNotAllowed;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignSuperAdminRole
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /** @return array{user: User, changed: bool} */
    public function execute(string $email): array
    {
        $operationId = Str::uuid()->toString();
        $normalizedEmail = Str::lower(trim($email));

        try {
            return DB::transaction(function () use ($normalizedEmail, $operationId): array {
                $role = Role::query()
                    ->where('name', RoleName::SuperAdmin->value)
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->lockForUpdate()
                    ->first();

                if ($role === null || ! $this->roleMatchesCatalog($role)) {
                    throw PrivilegeAssignmentNotAllowed::catalogNotSynchronized();
                }

                $user = User::query()
                    ->where('email', $normalizedEmail)
                    ->lockForUpdate()
                    ->first();

                if ($user === null) {
                    throw PrivilegeAssignmentNotAllowed::userNotFound();
                }

                if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
                    throw PrivilegeAssignmentNotAllowed::ineligibleAccount();
                }

                $currentRoles = $this->roleNamesFor($user);

                if (in_array(RoleName::SuperAdmin->value, $currentRoles, true)) {
                    return ['user' => $user, 'changed' => false];
                }

                $user->assignRole($role);
                $newRoles = [...$currentRoles, RoleName::SuperAdmin->value];
                sort($newRoles);

                $this->recordAudit->execute(
                    actor: null,
                    action: AuditAction::RoleChanged,
                    subjectType: 'user',
                    subjectId: $user->getKey(),
                    oldValues: ['roles' => $currentRoles],
                    newValues: ['roles' => $newRoles],
                    metadata: [
                        'source' => 'console',
                        'command' => 'authorization:super-admin',
                        'change' => 'role_assigned',
                    ],
                    requestId: $operationId,
                );

                return ['user' => $user, 'changed' => true];
            }, attempts: 3);
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }

    private function roleMatchesCatalog(Role $role): bool
    {
        $currentPermissions = $role->permissions()
            ->pluck('name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->sort()
            ->values()
            ->all();
        $catalogPermissions = AuthorizationCatalog::permissionsFor(RoleName::SuperAdmin);
        sort($catalogPermissions);

        return $currentPermissions === $catalogPermissions;
    }

    /** @return list<string> */
    private function roleNamesFor(User $user): array
    {
        $roleNames = $user->roles()
            ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
            ->pluck('name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->sort()
            ->values()
            ->all();

        return array_values($roleNames);
    }
}

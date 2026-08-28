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

class RenameAuthorizationRole
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /** @return array{role: Role, changed: bool} */
    public function execute(User $actor, Role $role, string $name): array
    {
        $operationId = Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($actor, $role, $name, $operationId): array {
                $lockedRole = Role::query()
                    ->whereKey($role->getKey())
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::forUser($actor)->authorize('update', $lockedRole);

                if ($lockedRole->name === $name) {
                    return ['role' => $lockedRole, 'changed' => false];
                }

                $oldName = $lockedRole->name;
                $lockedRole->name = $name;
                $lockedRole->save();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::RoleChanged,
                    subjectType: 'role',
                    subjectId: $lockedRole->getKey(),
                    oldValues: ['name' => $oldName],
                    newValues: ['name' => $lockedRole->name],
                    metadata: ['source' => 'web', 'change' => 'role_renamed'],
                    requestId: $operationId,
                );

                return ['role' => $lockedRole, 'changed' => true];
            }, attempts: 3);
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }
}

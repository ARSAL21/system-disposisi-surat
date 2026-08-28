<?php

namespace App\Actions;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateAuthorizationRole
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function execute(User $actor, string $name): Role
    {
        $operationId = Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($actor, $name, $operationId): Role {
                Role::query()
                    ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                    ->lockForUpdate()
                    ->get(['id']);

                $role = new Role([
                    'name' => $name,
                    'guard_name' => AuthorizationCatalog::GUARD_NAME,
                ]);
                $role->save();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::RoleChanged,
                    subjectType: 'role',
                    subjectId: $role->getKey(),
                    newValues: [
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                    ],
                    metadata: $this->metadata('role_created'),
                    requestId: $operationId,
                );

                return $role;
            }, attempts: 3);
        } finally {
            $this->permissionRegistrar->forgetCachedPermissions();
        }
    }

    /** @return array{source: string, change: string} */
    private function metadata(string $change): array
    {
        return ['source' => 'web', 'change' => $change];
    }
}

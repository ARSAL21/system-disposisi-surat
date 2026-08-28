<?php

namespace App\Auditing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PrivilegeAuditTargetResolver
{
    /**
     * @param  Collection<int, AuditLog>  $audits
     * @return array<string, array<int, array{label: string, secondary: string|null}>>
     */
    public function resolve(Collection $audits): array
    {
        $targets = [
            'user' => [],
            'role' => [],
            'permission' => [],
        ];

        foreach (User::query()->whereKey($this->subjectIds($audits, 'user'))->get(['id', 'name', 'email']) as $user) {
            $targets['user'][$user->getKey()] = ['label' => $user->name, 'secondary' => $user->email];
        }

        foreach (Role::query()->whereKey($this->subjectIds($audits, 'role'))->get(['id', 'name', 'guard_name']) as $role) {
            $targets['role'][$role->getKey()] = ['label' => $role->name, 'secondary' => "Guard {$role->guard_name}"];
        }

        foreach (Permission::query()->whereKey($this->subjectIds($audits, 'permission'))->get(['id', 'name', 'guard_name']) as $permission) {
            $targets['permission'][$permission->getKey()] = ['label' => $permission->name, 'secondary' => "Guard {$permission->guard_name}"];
        }

        return $targets;
    }

    /**
     * @param  Collection<int, AuditLog>  $audits
     * @return list<int>
     */
    private function subjectIds(Collection $audits, string $subjectType): array
    {
        return array_values($audits
            ->where('subject_type', $subjectType)
            ->pluck('subject_id')
            ->filter(fn (mixed $id): bool => is_int($id))
            ->unique()
            ->values()
            ->all());
    }
}

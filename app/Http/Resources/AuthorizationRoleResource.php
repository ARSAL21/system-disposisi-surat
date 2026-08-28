<?php

namespace App\Http\Resources;

use App\Authorization\AuthorizationCatalog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/** @mixin Role */
class AuthorizationRoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $actor = $actor instanceof User ? $actor : null;
        $protected = AuthorizationCatalog::isProtectedRole($this->name);
        $assignedToActor = $actor?->roles()
            ->whereKey($this->getKey())
            ->exists() ?? false;

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'is_protected' => $protected,
            'is_assigned_to_actor' => $assignedToActor,
            'user_count' => (int) ($this->users_count ?? 0),
            'permissions' => $this->permissions
                ->pluck('name')
                ->map(static fn (mixed $name): string => (string) $name)
                ->sort()
                ->values()
                ->all(),
            'capabilities' => [
                'rename' => $actor?->can('update', $this->resource) ?? false,
                'delete' => $actor?->can('delete', $this->resource) ?? false,
                'synchronize_permissions' => $actor?->can('synchronizePermissions', $this->resource) ?? false,
            ],
            'links' => [
                'update' => route('back-office.authorization.roles.update', $this->resource),
                'delete' => route('back-office.authorization.roles.destroy', $this->resource),
                'permissions' => route('back-office.authorization.roles.permissions.update', $this->resource),
            ],
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Authorization\AuthorizationCatalog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/** @mixin User */
class AuthorizationUserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $actor = $actor instanceof User ? $actor : null;
        $roles = [];

        foreach ($this->resource->getRelation('roles') as $role) {
            if (! $role instanceof Role) {
                continue;
            }

            $roles[] = [
                'id' => $role->getKey(),
                'name' => $role->name,
                'is_protected' => AuthorizationCatalog::isProtectedRole($role->name),
            ];
        }

        usort($roles, static fn (array $left, array $right): int => $left['name'] <=> $right['name']);

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'is_verified' => $this->hasVerifiedEmail(),
            'roles' => $roles,
            'capabilities' => [
                'synchronize_roles' => $actor?->can('synchronizeRoles', $this->resource) ?? false,
            ],
            'links' => [
                'roles' => route('back-office.authorization.users.roles.update', $this->resource),
            ],
        ];
    }
}

<?php

namespace App\Authorization;

use App\Enums\PermissionName;
use App\Enums\RoleName;

final class AuthorizationCatalog
{
    public const string GUARD_NAME = 'web';

    /** @return list<string> */
    public static function permissionNames(): array
    {
        return array_map(
            static fn (PermissionName $permission): string => $permission->value,
            PermissionName::cases(),
        );
    }

    /** @return list<string> */
    public static function roleNames(): array
    {
        return array_map(
            static fn (RoleName $role): string => $role->value,
            RoleName::cases(),
        );
    }

    /** @return list<string> */
    public static function permissionsFor(RoleName $role): array
    {
        return match ($role) {
            RoleName::SuperAdmin => [
                PermissionName::ViewAuthorization->value,
                PermissionName::ManageAuthorization->value,
                PermissionName::ManagePositionAssignments->value,
            ],
        };
    }
}

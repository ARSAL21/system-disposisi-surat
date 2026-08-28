<?php

namespace App\Authorization;

use App\Enums\PermissionName;
use App\Enums\RoleName;

final class AuthorizationCatalog
{
    public const string GUARD_NAME = 'web';

    /**
     * @return list<array{name: string, label: string, description: string, group: string}>
     */
    public static function permissionDefinitions(): array
    {
        return array_map(
            static fn (PermissionName $permission): array => match ($permission) {
                PermissionName::ViewAuthorization => [
                    'name' => $permission->value,
                    'label' => 'Lihat konfigurasi otorisasi',
                    'description' => 'Membaca role, permission resmi, dan assignment role user internal.',
                    'group' => 'Otorisasi',
                ],
                PermissionName::ManageAuthorization => [
                    'name' => $permission->value,
                    'label' => 'Kelola otorisasi',
                    'description' => 'Membuat custom role, mengatur permission, dan mengelola assignment role.',
                    'group' => 'Otorisasi',
                ],
                PermissionName::ManagePositionAssignments => [
                    'name' => $permission->value,
                    'label' => 'Kelola penugasan jabatan',
                    'description' => 'Mengelola lifecycle Position Assignment tanpa mengubah hierarki organisasi.',
                    'group' => 'Organisasi',
                ],
            },
            PermissionName::cases(),
        );
    }

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

    public static function isProtectedRole(string $roleName): bool
    {
        return in_array($roleName, self::roleNames(), true);
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

<?php

namespace App\Auditing;

use App\Enums\AuditAction;

final class PrivilegeAuditCatalog
{
    /** @return list<string> */
    public static function actions(): array
    {
        return [
            AuditAction::InternalAccountProvisioned->value,
            AuditAction::RoleChanged->value,
            AuditAction::PermissionChanged->value,
        ];
    }

    /** @return list<string> */
    public static function sources(): array
    {
        return ['web', 'console'];
    }

    /** @return list<string> */
    public static function targetTypes(): array
    {
        return ['user', 'role', 'permission'];
    }

    /** @return array<string, list<array{value: string, label: string}>> */
    public static function filterOptions(): array
    {
        return [
            'actions' => [
                ['value' => AuditAction::InternalAccountProvisioned->value, 'label' => 'Akun internal dibuat'],
                ['value' => AuditAction::RoleChanged->value, 'label' => 'Role berubah'],
                ['value' => AuditAction::PermissionChanged->value, 'label' => 'Permission berubah'],
            ],
            'sources' => [
                ['value' => 'web', 'label' => 'Web'],
                ['value' => 'console', 'label' => 'Console'],
            ],
            'target_types' => [
                ['value' => 'user', 'label' => 'Akun internal'],
                ['value' => 'role', 'label' => 'Role'],
                ['value' => 'permission', 'label' => 'Permission'],
            ],
        ];
    }
}

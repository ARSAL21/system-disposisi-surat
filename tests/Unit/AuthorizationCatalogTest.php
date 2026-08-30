<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\PermissionName;
use App\Enums\RoleName;

test('authorization catalog exposes unique role and permission names', function (): void {
    expect(AuthorizationCatalog::roleNames())
        ->toBe([RoleName::SuperAdmin->value])
        ->toHaveCount(count(array_unique(AuthorizationCatalog::roleNames())))
        ->and(AuthorizationCatalog::permissionNames())
        ->toBe([
            PermissionName::ViewAuthorization->value,
            PermissionName::ManageAuthorization->value,
            PermissionName::ViewOrganization->value,
            PermissionName::ManageOrganization->value,
            PermissionName::ManagePositionAssignments->value,
            PermissionName::ViewPrivilegeAudits->value,
            PermissionName::ViewLetterActivities->value,
            PermissionName::ViewIntake->value,
            PermissionName::ScreenIntake->value,
            PermissionName::DecideIntake->value,
        ])
        ->toHaveCount(count(array_unique(AuthorizationCatalog::permissionNames())));
});

test('super admin receives only the permissions explicitly listed in the catalog', function (): void {
    expect(AuthorizationCatalog::permissionsFor(RoleName::SuperAdmin))
        ->toBe(AuthorizationCatalog::permissionNames());
});

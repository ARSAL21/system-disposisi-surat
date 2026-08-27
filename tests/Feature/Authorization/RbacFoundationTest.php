<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createCatalogSuperAdminRole(): Role
{
    $permissions = array_map(
        static fn (string $permission): Permission => Permission::findOrCreate(
            $permission,
            AuthorizationCatalog::GUARD_NAME,
        ),
        AuthorizationCatalog::permissionsFor(RoleName::SuperAdmin),
    );

    $role = Role::findOrCreate(
        RoleName::SuperAdmin->value,
        AuthorizationCatalog::GUARD_NAME,
    );
    $role->syncPermissions($permissions);

    return $role;
}

test('standard RBAC tables are available', function (): void {
    expect(Schema::hasTable('roles'))->toBeTrue()
        ->and(Schema::hasTable('permissions'))->toBeTrue()
        ->and(Schema::hasTable('model_has_roles'))->toBeTrue()
        ->and(Schema::hasTable('model_has_permissions'))->toBeTrue()
        ->and(Schema::hasTable('role_has_permissions'))->toBeTrue();
});

test('an internal super admin receives only explicit catalog permissions', function (): void {
    $role = createCatalogSuperAdminRole();
    Permission::findOrCreate('letters.view-all', AuthorizationCatalog::GUARD_NAME);

    $user = User::factory()->internal()->create();
    $user->assignRole($role);

    expect($user->hasRole(RoleName::SuperAdmin->value))->toBeTrue()
        ->and($user->can(PermissionName::ViewAuthorization->value))->toBeTrue()
        ->and($user->can(PermissionName::ManageAuthorization->value))->toBeTrue()
        ->and($user->can('letters.view-all'))->toBeFalse();
});

test('an internal user has no implicit authorization permissions', function (): void {
    Permission::findOrCreate(
        PermissionName::ManageAuthorization->value,
        AuthorizationCatalog::GUARD_NAME,
    );

    $user = User::factory()->internal()->create();

    expect($user->can(PermissionName::ManageAuthorization->value))->toBeFalse()
        ->and($user->roles)->toBeEmpty();
});

test('a role cannot bypass the public and internal account boundary', function (): void {
    $user = User::factory()->create();
    $user->assignRole(createCatalogSuperAdminRole());

    expect($user->can(PermissionName::ManageAuthorization->value))->toBeTrue();

    $this->actingAs($user)
        ->get(route('internal.dashboard'))
        ->assertNotFound();
});

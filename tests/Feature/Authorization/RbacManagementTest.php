<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createAuthorizationManager(User $user): Role
{
    $permissions = collect([
        PermissionName::ViewAuthorization,
        PermissionName::ManageAuthorization,
    ])->map(fn (PermissionName $permission): Permission => Permission::findOrCreate(
        $permission->value,
        AuthorizationCatalog::GUARD_NAME,
    ));
    $role = Role::findOrCreate('authorization-manager', AuthorizationCatalog::GUARD_NAME);
    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $role;
}

function confirmedPasswordSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

test('authorization workspace exposes official read model to an authorized internal user', function (): void {
    $viewer = User::factory()->internal()->create();
    $permission = Permission::findOrCreate(
        PermissionName::ViewAuthorization->value,
        AuthorizationCatalog::GUARD_NAME,
    );
    $role = Role::findOrCreate('authorization-viewer', AuthorizationCatalog::GUARD_NAME);
    $role->givePermissionTo($permission);
    $viewer->assignRole($role);

    $this->actingAs($viewer)
        ->get(route('back-office.authorization.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/authorization/Index')
            ->where('mutationSecurity.can_manage', false)
            ->where('mutationSecurity.can_mutate', false)
            ->has('roles', 1)
            ->has('permissions', count(AuthorizationCatalog::permissionNames()))
            ->has('users.data', 1));
});

test('public accounts and internal accounts without permission cannot view authorization', function (): void {
    $publicUser = User::factory()->create();
    $publicRole = Role::findOrCreate('public-viewer', AuthorizationCatalog::GUARD_NAME);
    $publicRole->givePermissionTo(Permission::findOrCreate(
        PermissionName::ViewAuthorization->value,
        AuthorizationCatalog::GUARD_NAME,
    ));
    $publicUser->assignRole($publicRole);

    $this->actingAs($publicUser)
        ->get(route('back-office.authorization.index'))
        ->assertNotFound();

    $this->actingAs(User::factory()->internal()->create())
        ->get(route('back-office.authorization.index'))
        ->assertForbidden();
});

test('authorization mutations require manage permission MFA and recent password confirmation', function (): void {
    $viewer = User::factory()->internal()->withTwoFactor()->create();
    $viewerRole = Role::findOrCreate('viewer', AuthorizationCatalog::GUARD_NAME);
    $viewerRole->givePermissionTo(Permission::findOrCreate(
        PermissionName::ViewAuthorization->value,
        AuthorizationCatalog::GUARD_NAME,
    ));
    $viewer->assignRole($viewerRole);

    $this->actingAs($viewer)
        ->withSession(confirmedPasswordSession())
        ->post(route('back-office.authorization.roles.store'), ['name' => 'operator'])
        ->assertForbidden();

    $managerWithoutMfa = User::factory()->internal()->create();
    createAuthorizationManager($managerWithoutMfa);

    $this->actingAs($managerWithoutMfa)
        ->withSession(confirmedPasswordSession())
        ->post(route('back-office.authorization.roles.store'), ['name' => 'operator'])
        ->assertRedirect(route('security.edit'));

    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);

    $this->actingAs($manager)
        ->withSession(['auth.password_confirmed_at' => 0])
        ->post(route('back-office.authorization.roles.store'), ['name' => 'operator'])
        ->assertRedirect(route('back-office.password.confirm'));
});

test('back office password confirmation is isolated and expires after fifteen minutes', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);

    $this->actingAs($manager)
        ->get(route('back-office.password.confirm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/auth/ConfirmPassword')
            ->where('confirmPasswordUrl', route('back-office.password.confirm.store')));

    $this->actingAs($manager)
        ->withSession(['auth.password_confirmed_at' => time() - 901])
        ->postJson(route('back-office.authorization.roles.store'), ['name' => 'operator'])
        ->assertStatus(423);

    expect(Role::query()->where('name', 'operator')->exists())->toBeFalse();
});

test('manager can create rename and delete an unused custom role with atomic audits', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->post(route('back-office.authorization.roles.store'), ['name' => 'operator-surat'])
        ->assertRedirect(route('back-office.authorization.index'));

    $role = Role::query()->where('name', 'operator-surat')->firstOrFail();

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->patch(route('back-office.authorization.roles.update', $role), ['name' => 'operator-intake'])
        ->assertRedirect();

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->delete(route('back-office.authorization.roles.destroy', $role))
        ->assertRedirect(route('back-office.authorization.index'));

    expect(Role::query()->whereKey($role->getKey())->exists())->toBeFalse()
        ->and(AuditLog::query()
            ->where('actor_user_id', $manager->getKey())
            ->where('action', AuditAction::RoleChanged->value)
            ->count())->toBe(3);
});

test('custom role names must be lowercase kebab case unique and not protected', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);
    Role::findOrCreate('operator-surat', AuthorizationCatalog::GUARD_NAME);

    foreach (['Operator Surat', 'operator_surat', RoleName::SuperAdmin->value, 'operator-surat'] as $name) {
        $this->actingAs($manager)
            ->withSession(confirmedPasswordSession())
            ->post(route('back-office.authorization.roles.store'), ['name' => $name])
            ->assertInvalid('name');
    }
});

test('manager can synchronize only official permissions on a custom role', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);
    foreach (AuthorizationCatalog::permissionNames() as $permissionName) {
        Permission::findOrCreate($permissionName, AuthorizationCatalog::GUARD_NAME);
    }
    $role = Role::findOrCreate('operator-surat', AuthorizationCatalog::GUARD_NAME);

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->put(route('back-office.authorization.roles.permissions.update', $role), [
            'permissions' => [
                PermissionName::ViewAuthorization->value,
                PermissionName::ManagePositionAssignments->value,
            ],
        ])
        ->assertRedirect();

    expect($role->fresh()->permissions()->pluck('name')->sort()->values()->all())
        ->toBe([
            PermissionName::ViewAuthorization->value,
            PermissionName::ManagePositionAssignments->value,
        ])
        ->and(AuditLog::query()
            ->where('actor_user_id', $manager->getKey())
            ->where('action', AuditAction::PermissionChanged->value)
            ->exists())->toBeTrue();

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->put(route('back-office.authorization.roles.permissions.update', $role), [
            'permissions' => ['letters.view-all'],
        ])
        ->assertInvalid('permissions.0');
});

test('protected role and roles held by the actor are read only through web', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    $managerRole = createAuthorizationManager($manager);
    $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, AuthorizationCatalog::GUARD_NAME);

    foreach ([$managerRole, $superAdmin] as $role) {
        $this->actingAs($manager)
            ->withSession(confirmedPasswordSession())
            ->patch(route('back-office.authorization.roles.update', $role), ['name' => 'changed-role'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->withSession(confirmedPasswordSession())
            ->put(route('back-office.authorization.roles.permissions.update', $role), [
                'permissions' => [],
            ])
            ->assertForbidden();
    }
});

test('role assignments exact sync custom roles while preserving protected roles', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);
    $target = User::factory()->internal()->create();
    $firstRole = Role::findOrCreate('operator-surat', AuthorizationCatalog::GUARD_NAME);
    $secondRole = Role::findOrCreate('auditor-internal', AuthorizationCatalog::GUARD_NAME);
    $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, AuthorizationCatalog::GUARD_NAME);
    $target->assignRole([$firstRole, $superAdmin]);

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->put(route('back-office.authorization.users.roles.update', $target), [
            'role_ids' => [$secondRole->getKey()],
        ])
        ->assertRedirect();

    expect($target->fresh()->roles()->pluck('name')->sort()->values()->all())
        ->toBe([$secondRole->name, $superAdmin->name]);
});

test('inactive internal account can lose existing roles but cannot receive a new role', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);
    $target = User::factory()->internal()->inactive()->create();
    $currentRole = Role::findOrCreate('operator-surat', AuthorizationCatalog::GUARD_NAME);
    $newRole = Role::findOrCreate('auditor-internal', AuthorizationCatalog::GUARD_NAME);
    $target->assignRole($currentRole);

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->put(route('back-office.authorization.users.roles.update', $target), [
            'role_ids' => [$currentRole->getKey(), $newRole->getKey()],
        ])
        ->assertInvalid('role_ids');

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->put(route('back-office.authorization.users.roles.update', $target), [
            'role_ids' => [],
        ])
        ->assertRedirect();

    expect($target->fresh()->roles)->toBeEmpty();
});

test('self role assignment is forbidden and a public target is not found', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    $managerRole = createAuthorizationManager($manager);
    $publicUser = User::factory()->create();

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->put(route('back-office.authorization.users.roles.update', $manager), [
            'role_ids' => [$managerRole->getKey()],
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->put('/back-office/authorization/users/'.$publicUser->getKey().'/roles', [
            'role_ids' => [],
        ])
        ->assertNotFound();
});

test('a role assigned to a user cannot be deleted', function (): void {
    $manager = User::factory()->internal()->withTwoFactor()->create();
    createAuthorizationManager($manager);
    $target = User::factory()->internal()->create();
    $role = Role::findOrCreate('operator-surat', AuthorizationCatalog::GUARD_NAME);
    $target->assignRole($role);

    $this->actingAs($manager)
        ->withSession(confirmedPasswordSession())
        ->delete(route('back-office.authorization.roles.destroy', $role))
        ->assertConflict();

    expect($role->fresh())->not->toBeNull();
});

<?php

use App\Actions\SynchronizeAuthorizationCatalog;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(SynchronizeAuthorizationCatalog::class)->execute();
});

test('super admin command grants and revokes through confirmed console operations', function (): void {
    $first = User::factory()->internal()->create(['email' => 'first@example.test']);
    $second = User::factory()->internal()->create(['email' => 'second@example.test']);

    $this->artisan('authorization:super-admin', ['email' => $first->email])
        ->expectsConfirmation('Assign the super-admin role to first@example.test?', 'yes')
        ->assertSuccessful();
    $this->artisan('authorization:super-admin', ['email' => $second->email])
        ->expectsConfirmation('Assign the super-admin role to second@example.test?', 'yes')
        ->assertSuccessful();
    $this->artisan('authorization:super-admin', [
        'email' => $first->email,
        '--revoke' => true,
    ])->expectsConfirmation('Revoke the super-admin role from first@example.test?', 'yes')
        ->assertSuccessful();

    expect($first->fresh()->hasRole(RoleName::SuperAdmin->value))->toBeFalse()
        ->and($second->fresh()->hasRole(RoleName::SuperAdmin->value))->toBeTrue()
        ->and(AuditLog::query()
            ->where('action', AuditAction::RoleChanged->value)
            ->where('metadata->change', 'role_revoked')
            ->exists())->toBeTrue();
});

test('super admin command refuses to revoke the final holder', function (): void {
    $user = User::factory()->internal()->create(['email' => 'admin@example.test']);
    $role = Role::query()->where('name', RoleName::SuperAdmin->value)->firstOrFail();
    $user->assignRole($role);

    $this->artisan('authorization:super-admin', [
        'email' => $user->email,
        '--revoke' => true,
    ])->expectsConfirmation('Revoke the super-admin role from admin@example.test?', 'yes')
        ->assertFailed();

    expect($user->fresh()->hasRole(RoleName::SuperAdmin->value))->toBeTrue();
});

test('authorization sync preserves custom roles without reporting them as drift', function (): void {
    $customRole = Role::findOrCreate('operator-surat', AuthorizationCatalog::GUARD_NAME);

    $result = app(SynchronizeAuthorizationCatalog::class)->execute();

    expect($result)->not->toHaveKey('unknown_roles')
        ->and($customRole->fresh())->not->toBeNull();
});

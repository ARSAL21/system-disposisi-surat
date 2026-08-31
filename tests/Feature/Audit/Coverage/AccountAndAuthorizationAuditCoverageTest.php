<?php

use App\Actions\ProvisionInternalUser;
use App\Actions\SynchronizeRolePermissions;
use App\Actions\SynchronizeUserRoles;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('verifies audit emission for internal account provisioning', function (): void {
    $initialAuditCount = AuditLog::count();

    $user = app(ProvisionInternalUser::class)->execute([
        'name' => 'Pegawai Arsip',
        'email' => 'arsip@gmail.com',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ]);

    expect(AuditLog::count())->toBe($initialAuditCount + 1);

    $audit = AuditLog::where('action', AuditAction::InternalAccountProvisioned->value)
        ->where('subject_id', $user->id)
        ->firstOrFail();

    expect($audit->actor_user_id)->toBeNull()
        ->and($audit->actor_position_assignment_id)->toBeNull()
        ->and($audit->subject_type)->toBe('user')
        ->and($audit->old_values)->toBeNull()
        ->and($audit->new_values)->toHaveKeys(['name', 'email', 'account_type', 'is_active', 'email_verified_at'])
        ->and($audit->new_values['email'])->toBe('arsip@gmail.com')
        ->and($audit->new_values['account_type'])->toBe(AccountType::InternalAccount->value)
        ->and($audit->new_values['is_active'])->toBeTrue()
        ->and($audit->request_id)->toMatch('/^[A-Za-z0-9_\-:.]+$/');
});

it('verifies audit emission for user roles synchronization', function (): void {
    $admin = User::factory()->internal()->create();
    $managePerm = Permission::findOrCreate(PermissionName::ManageAuthorization->value, AuthorizationCatalog::GUARD_NAME);
    $adminRole = Role::findOrCreate('admin-role', AuthorizationCatalog::GUARD_NAME);
    $adminRole->givePermissionTo($managePerm);
    $admin->assignRole($adminRole);

    $targetUser = User::factory()->internal()->create();
    $roleToAssign = Role::findOrCreate('editor-role', AuthorizationCatalog::GUARD_NAME);

    $initialAuditCount = AuditLog::count();

    app(SynchronizeUserRoles::class)->execute(
        actor: $admin,
        target: $targetUser,
        requestedRoleIds: [$roleToAssign->id],
    );

    expect(AuditLog::count())->toBe($initialAuditCount + 1);

    $audit = AuditLog::where('action', AuditAction::RoleChanged->value)
        ->where('subject_id', $targetUser->id)
        ->firstOrFail();

    expect($audit->actor_user_id)->toBe($admin->id)
        ->and($audit->actor_position_assignment_id)->toBeNull()
        ->and($audit->subject_type)->toBe('user')
        ->and($audit->old_values)->toBe(['roles' => []])
        ->and($audit->new_values)->toBe(['roles' => ['editor-role']])
        ->and($audit->request_id)->toMatch('/^[A-Za-z0-9_\-:.]+$/');
});

it('verifies audit emission for role permissions synchronization', function (): void {
    $admin = User::factory()->internal()->create();
    $managePerm = Permission::findOrCreate(PermissionName::ManageAuthorization->value, AuthorizationCatalog::GUARD_NAME);
    $adminRole = Role::findOrCreate('admin-role', AuthorizationCatalog::GUARD_NAME);
    $adminRole->givePermissionTo($managePerm);
    $admin->assignRole($adminRole);

    $targetRole = Role::findOrCreate('staff-role', AuthorizationCatalog::GUARD_NAME);
    $viewIntakePerm = Permission::findOrCreate(PermissionName::ViewIntake->value, AuthorizationCatalog::GUARD_NAME);

    $initialAuditCount = AuditLog::count();

    app(SynchronizeRolePermissions::class)->execute(
        actor: $admin,
        role: $targetRole,
        permissionNames: [PermissionName::ViewIntake->value],
    );

    expect(AuditLog::count())->toBe($initialAuditCount + 1);

    $audit = AuditLog::where('action', AuditAction::PermissionChanged->value)
        ->where('subject_id', $targetRole->id)
        ->firstOrFail();

    expect($audit->actor_user_id)->toBe($admin->id)
        ->and($audit->actor_position_assignment_id)->toBeNull()
        ->and($audit->subject_type)->toBe('role')
        ->and($audit->old_values)->toBe(['permissions' => []])
        ->and($audit->new_values)->toBe(['permissions' => [PermissionName::ViewIntake->value]])
        ->and($audit->request_id)->toMatch('/^[A-Za-z0-9_\-:.]+$/');
});

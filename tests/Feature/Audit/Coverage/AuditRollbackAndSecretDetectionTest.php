<?php

use App\Actions\AssignUserToPosition;
use App\Actions\CreateOnlineSubmission;
use App\Actions\CreateOrganizationalUnit;
use App\Actions\RecordAudit;
use App\Actions\SynchronizeRolePermissions;
use App\Actions\SynchronizeUserRoles;
use App\Auditing\Exceptions\AuditContractViolationException;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\LetterSubmission;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('submission-documents');
    Storage::fake('letter-documents');
});

it('verifies that an audit violation during CreateOrganizationalUnit rolls back the database unit record', function (): void {
    $admin = User::factory()->internal()->create();

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(AuditContractViolationException::forAction(
                AuditAction::OrganizationalUnitCreated->value,
                'Simulated audit violation for testing rollback.',
            ));
    });

    expect(function () use ($admin): void {
        app(CreateOrganizationalUnit::class)->execute($admin, [
            'parent_id' => null,
            'code' => 'UNIT_ROLLBACK_ACTION',
            'name' => 'Unit Rollback Action',
        ]);
    })->toThrow(AuditContractViolationException::class);

    expect(OrganizationalUnit::where('code', 'UNIT_ROLLBACK_ACTION')->exists())->toBeFalse()
        ->and(AuditLog::where('action', AuditAction::OrganizationalUnitCreated->value)->exists())->toBeFalse();
});

it('verifies that an audit violation during AssignUserToPosition rolls back the assignment record', function (): void {
    $admin = User::factory()->internal()->create();
    $assignee = User::factory()->internal()->create();

    $level = PositionLevel::where('code', OrganizationCatalog::GENERAL_AFFAIRS_LEVEL)->first();
    if (! $level instanceof PositionLevel) {
        $level = new PositionLevel;
        $level->code = OrganizationCatalog::GENERAL_AFFAIRS_LEVEL;
        $level->name = 'General Affairs';
        $level->hierarchy_order = 10;
        $level->is_active = true;
        $level->save();
    }

    $unit = new OrganizationalUnit;
    $unit->code = 'UNIT_ASSIGN_ROLLBACK';
    $unit->name = 'Unit Assign Rollback';
    $unit->is_active = true;
    $unit->save();

    $position = new Position;
    $position->position_level_id = $level->id;
    $position->organizational_unit_id = $unit->id;
    $position->code = 'POS_ASSIGN_ROLLBACK';
    $position->name = 'Posisi Assign Rollback';
    $position->is_active = true;
    $position->save();

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(AuditContractViolationException::forAction(
                AuditAction::PositionAssigned->value,
                'Simulated audit violation on position assignment.',
            ));
    });

    expect(function () use ($admin, $assignee, $position): void {
        app(AssignUserToPosition::class)->execute($admin, $assignee, $position);
    })->toThrow(AuditContractViolationException::class);

    expect(PositionAssignment::where('position_id', $position->id)->exists())->toBeFalse()
        ->and(AuditLog::where('action', AuditAction::PositionAssigned->value)->exists())->toBeFalse();
});

it('verifies that an audit violation during SynchronizeUserRoles rolls back role modifications', function (): void {
    $admin = User::factory()->internal()->create();
    $managePerm = Permission::findOrCreate(PermissionName::ManageAuthorization->value, AuthorizationCatalog::GUARD_NAME);
    $adminRole = Role::findOrCreate('admin-role', AuthorizationCatalog::GUARD_NAME);
    $adminRole->givePermissionTo($managePerm);
    $admin->assignRole($adminRole);

    $targetUser = User::factory()->internal()->create();
    $roleToAssign = Role::findOrCreate('analyst-role', AuthorizationCatalog::GUARD_NAME);

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(AuditContractViolationException::forAction(
                AuditAction::RoleChanged->value,
                'Simulated audit violation on role sync.',
            ));
    });

    expect(function () use ($admin, $targetUser, $roleToAssign): void {
        app(SynchronizeUserRoles::class)->execute(
            actor: $admin,
            target: $targetUser,
            requestedRoleIds: [$roleToAssign->id],
        );
    })->toThrow(AuditContractViolationException::class);

    expect($targetUser->fresh()->hasRole('analyst-role'))->toBeFalse()
        ->and(AuditLog::where('action', AuditAction::RoleChanged->value)->exists())->toBeFalse();
});

it('verifies that an audit violation during SynchronizeRolePermissions rolls back permission modifications', function (): void {
    $admin = User::factory()->internal()->create();
    $managePerm = Permission::findOrCreate(PermissionName::ManageAuthorization->value, AuthorizationCatalog::GUARD_NAME);
    $adminRole = Role::findOrCreate('admin-role', AuthorizationCatalog::GUARD_NAME);
    $adminRole->givePermissionTo($managePerm);
    $admin->assignRole($adminRole);

    $targetRole = Role::findOrCreate('reviewer-role', AuthorizationCatalog::GUARD_NAME);
    $viewIntakePerm = Permission::findOrCreate(PermissionName::ViewIntake->value, AuthorizationCatalog::GUARD_NAME);

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(AuditContractViolationException::forAction(
                AuditAction::PermissionChanged->value,
                'Simulated audit violation on permission sync.',
            ));
    });

    expect(function () use ($admin, $targetRole, $viewIntakePerm): void {
        app(SynchronizeRolePermissions::class)->execute(
            actor: $admin,
            role: $targetRole,
            permissionNames: [$viewIntakePerm->name],
        );
    })->toThrow(AuditContractViolationException::class);

    expect($targetRole->fresh()->hasPermissionTo(PermissionName::ViewIntake->value))->toBeFalse()
        ->and(AuditLog::where('action', AuditAction::PermissionChanged->value)->exists())->toBeFalse();
});

it('verifies that an audit violation during CreateOnlineSubmission rolls back the submission record', function (): void {
    $owner = User::factory()->create(['account_type' => AccountType::PublicAccount]);

    $this->mock(RecordAudit::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(AuditContractViolationException::forAction(
                AuditAction::SubmissionCreated->value,
                'Simulated audit violation on submission creation.',
            ));
    });

    expect(function () use ($owner): void {
        app(CreateOnlineSubmission::class)->execute($owner, [
            'sender_organization_name' => 'Komunitas Rollback',
            'contact_name' => 'Warga',
            'contact_email' => 'warga@gmail.com',
            'subject' => 'Surat Batal Tersimpan',
        ]);
    })->toThrow(AuditContractViolationException::class);

    expect(LetterSubmission::where('subject', 'Surat Batal Tersimpan')->exists())->toBeFalse()
        ->and(AuditLog::where('action', AuditAction::SubmissionCreated->value)->exists())->toBeFalse();
});

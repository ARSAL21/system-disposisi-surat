<?php

use App\Actions\AssignUserToPosition;
use App\Actions\ChangeOrganizationalUnitStatus;
use App\Actions\ChangePositionStatus;
use App\Actions\CreateOrganizationalUnit;
use App\Actions\CreatePosition;
use App\Actions\EndPositionAssignment;
use App\Actions\ReplacePositionHolder;
use App\Actions\SynchronizePositionLevelCatalog;
use App\Actions\UpdateOrganizationalUnit;
use App\Actions\UpdatePosition;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PositionLevel;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('verifies audit emission for organizational units lifecycle', function (): void {
    $admin = User::factory()->internal()->create();

    // 1. Create Organizational Unit
    $unit = app(CreateOrganizationalUnit::class)->execute($admin, [
        'parent_id' => null,
        'code' => 'UNIT_ORG_TEST',
        'name' => 'Unit Organisasi Uji',
    ]);

    $auditCreate = AuditLog::where('action', AuditAction::OrganizationalUnitCreated->value)
        ->where('subject_id', $unit->id)
        ->firstOrFail();

    expect($auditCreate->actor_user_id)->toBe($admin->id)
        ->and($auditCreate->subject_type)->toBe('organizational_unit')
        ->and($auditCreate->old_values)->toBeNull()
        ->and($auditCreate->new_values)->toMatchArray([
            'code' => 'UNIT_ORG_TEST',
            'name' => 'Unit Organisasi Uji',
            'is_active' => true,
        ]);

    // 2. Update Organizational Unit
    $unit = app(UpdateOrganizationalUnit::class)->execute($admin, $unit, [
        'parent_id' => null,
        'name' => 'Unit Organisasi Uji Diperbarui',
    ]);

    $auditUpdate = AuditLog::where('action', AuditAction::OrganizationalUnitUpdated->value)
        ->where('subject_id', $unit->id)
        ->firstOrFail();

    expect($auditUpdate->actor_user_id)->toBe($admin->id)
        ->and($auditUpdate->old_values['name'])->toBe('Unit Organisasi Uji')
        ->and($auditUpdate->new_values['name'])->toBe('Unit Organisasi Uji Diperbarui');

    // 3. Change Organizational Unit Status
    app(ChangeOrganizationalUnitStatus::class)->execute($admin, $unit, false);

    $auditStatus = AuditLog::where('action', AuditAction::OrganizationalUnitStatusChanged->value)
        ->where('subject_id', $unit->id)
        ->firstOrFail();

    expect($auditStatus->actor_user_id)->toBe($admin->id)
        ->and($auditStatus->old_values['is_active'])->toBeTrue()
        ->and($auditStatus->new_values['is_active'])->toBeFalse();
});

it('verifies audit emission for position management and assignment lifecycle', function (): void {
    $admin = User::factory()->internal()->create();

    $unit = app(CreateOrganizationalUnit::class)->execute($admin, [
        'parent_id' => null,
        'code' => 'UNIT_POS_TEST',
        'name' => 'Unit Posisi Uji',
    ]);

    $level = PositionLevel::where('code', OrganizationCatalog::GENERAL_AFFAIRS_LEVEL)->first();
    if (! $level instanceof PositionLevel) {
        $level = new PositionLevel;
        $level->code = OrganizationCatalog::GENERAL_AFFAIRS_LEVEL;
        $level->name = 'General Affairs Level';
        $level->hierarchy_order = 10;
        $level->is_active = true;
        $level->save();
    }

    // 1. Create Position
    $position = app(CreatePosition::class)->execute($admin, [
        'code' => 'POS_ORG_TEST',
        'name' => 'Posisi Organisasi Uji',
        'position_level_id' => $level->id,
        'organizational_unit_id' => $unit->id,
    ]);

    $auditPosCreate = AuditLog::where('action', AuditAction::PositionCreated->value)
        ->where('subject_id', $position->id)
        ->firstOrFail();

    expect($auditPosCreate->actor_user_id)->toBe($admin->id)
        ->and($auditPosCreate->subject_type)->toBe('position')
        ->and($auditPosCreate->new_values['code'])->toBe('POS_ORG_TEST');

    // 2. Update Position
    $position = app(UpdatePosition::class)->execute($admin, $position, [
        'name' => 'Posisi Organisasi Diperbarui',
        'organizational_unit_id' => $unit->id,
    ]);

    $auditPosUpdate = AuditLog::where('action', AuditAction::PositionUpdated->value)
        ->where('subject_id', $position->id)
        ->firstOrFail();

    expect($auditPosUpdate->actor_user_id)->toBe($admin->id)
        ->and($auditPosUpdate->new_values['name'])->toBe('Posisi Organisasi Diperbarui');

    // 3. Change Position Status
    app(ChangePositionStatus::class)->execute($admin, $position, false);

    $auditPosStatus = AuditLog::where('action', AuditAction::PositionStatusChanged->value)
        ->where('subject_id', $position->id)
        ->firstOrFail();

    expect($auditPosStatus->actor_user_id)->toBe($admin->id)
        ->and($auditPosStatus->new_values['is_active'])->toBeFalse();

    // Reactivate position for assignment testing
    app(ChangePositionStatus::class)->execute($admin, $position, true);

    // 4. Position Assigned
    $assignee1 = User::factory()->internal()->create();
    $assignee2 = User::factory()->internal()->create();

    $assignment1 = app(AssignUserToPosition::class)->execute($admin, $assignee1, $position);

    $auditAssigned = AuditLog::where('action', AuditAction::PositionAssigned->value)
        ->where('subject_id', $position->id)
        ->firstOrFail();

    expect($auditAssigned->actor_user_id)->toBe($admin->id)
        ->and($auditAssigned->subject_type)->toBe('position')
        ->and($auditAssigned->new_values['assignment_id'])->toBe($assignment1->id)
        ->and($auditAssigned->new_values['user_id'])->toBe($assignee1->id);

    // 5. Position Holder Replaced
    $assignment2 = app(ReplacePositionHolder::class)->execute($admin, $assignee2, $position);

    $auditReplaced = AuditLog::where('action', AuditAction::PositionHolderReplaced->value)
        ->where('subject_id', $position->id)
        ->firstOrFail();

    expect($auditReplaced->actor_user_id)->toBe($admin->id)
        ->and($auditReplaced->old_values['assignment_id'])->toBe($assignment1->id)
        ->and($auditReplaced->new_values['assignment_id'])->toBe($assignment2->id);

    // 6. Position Assignment Ended
    app(EndPositionAssignment::class)->execute($admin, $assignment2);

    $auditEnded = AuditLog::where('action', AuditAction::PositionAssignmentEnded->value)
        ->where('subject_id', $position->id)
        ->firstOrFail();

    expect($auditEnded->actor_user_id)->toBe($admin->id)
        ->and($auditEnded->old_values['assignment_id'])->toBe($assignment2->id);

    // 7. Synchronize Position Level Catalog
    app(SynchronizePositionLevelCatalog::class)->execute();

    $auditCatalog = AuditLog::where('action', AuditAction::PositionLevelCatalogSynchronized->value)
        ->firstOrFail();

    expect($auditCatalog->actor_user_id)->toBeNull()
        ->and($auditCatalog->subject_type)->toBe('position_level_catalog')
        ->and($auditCatalog->subject_id)->toBeNull()
        ->and($auditCatalog->metadata['source'])->toBe('console')
        ->and($auditCatalog->metadata['command'])->toBe('organization:sync-levels');
});

<?php

use App\Enums\PermissionName;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('authorized internal administrator can manage position assignments', function (): void {
    $administrator = PositionAssignmentTestData::internalUser();
    PositionAssignmentTestData::grantSuperAdminRole($administrator);
    $assignment = PositionAssignmentTestData::assignment(
        PositionAssignmentTestData::internalUser(),
        PositionAssignmentTestData::position(),
        $administrator,
    );

    expect(Gate::forUser($administrator)->allows('assign', PositionAssignment::class))->toBeTrue()
        ->and(Gate::forUser($administrator)->allows('replace', PositionAssignment::class))->toBeTrue()
        ->and(Gate::forUser($administrator)->allows('end', $assignment))->toBeTrue();
});

test('internal user without explicit permission cannot manage assignments', function (): void {
    $user = PositionAssignmentTestData::internalUser();

    expect(Gate::forUser($user)->denies('assign', PositionAssignment::class))->toBeTrue();
});

test('public account cannot cross the assignment boundary even with permission', function (): void {
    $publicUser = User::factory()->create();
    Permission::findOrCreate(PermissionName::ManagePositionAssignments->value)
        ->assignRole($role = Role::findOrCreate('boundary-test'));
    $publicUser->assignRole($role);

    expect($publicUser->can(PermissionName::ManagePositionAssignments->value))->toBeTrue()
        ->and(Gate::forUser($publicUser)->denies('assign', PositionAssignment::class))->toBeTrue();
});

test('inactive or unverified administrator cannot manage assignments', function (): void {
    $inactive = PositionAssignmentTestData::internalUser(['is_active' => false]);
    $unverified = PositionAssignmentTestData::internalUser(['email_verified_at' => null]);
    PositionAssignmentTestData::grantSuperAdminRole($inactive);
    PositionAssignmentTestData::grantSuperAdminRole($unverified);

    expect(Gate::forUser($inactive)->denies('assign', PositionAssignment::class))->toBeTrue()
        ->and(Gate::forUser($unverified)->denies('assign', PositionAssignment::class))->toBeTrue();
});

<?php

use App\Actions\SynchronizePositionLevelCatalog;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(SynchronizePositionLevelCatalog::class)->execute();
});

function positionAssignmentManager(): User
{
    $user = User::factory()->internal()->withTwoFactor()->create();
    $role = Role::findOrCreate('position-assignment-manager', AuthorizationCatalog::GUARD_NAME);
    $role->syncPermissions([
        Permission::findOrCreate(PermissionName::ViewOrganization->value),
        Permission::findOrCreate(PermissionName::ManagePositionAssignments->value),
    ]);
    $user->assignRole($role);

    return $user;
}

function assignmentPosition(): Position
{
    $level = PositionLevel::query()->where('code', 'GENERAL_AFFAIRS')->firstOrFail();
    $position = new Position;
    $position->position_level_id = $level->getKey();
    $position->organizational_unit_id = null;
    $position->code = 'GENERAL_AFFAIRS_OPERATOR';
    $position->name = 'Operator Bagian Umum';
    $position->is_active = true;
    $position->save();

    return $position;
}

function assignmentConfirmedSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

test('assignment workspace exposes current holder and paginated history', function (): void {
    $manager = positionAssignmentManager();
    $position = assignmentPosition();
    $holder = User::factory()->internal()->create();
    $assignment = new PositionAssignment;
    $assignment->position_id = $position->getKey();
    $assignment->user_id = $holder->getKey();
    $assignment->started_at = now()->subDay();
    $assignment->assigned_by_user_id = $manager->getKey();
    $assignment->save();

    $this->actingAs($manager)
        ->get(route('back-office.organization.assignments.index', ['selected_position' => $position->getKey()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/organization/assignments/Index')
            ->where('positions.data.0.active_assignment.user.id', $holder->getKey())
            ->where('selectedPosition.id', $position->getKey())
            ->has('history.data', 1));
});

test('position assignment lifecycle is server timed atomic and audited', function (): void {
    $manager = positionAssignmentManager();
    $position = assignmentPosition();
    $firstHolder = User::factory()->internal()->create();
    $secondHolder = User::factory()->internal()->create();

    $this->actingAs($manager)
        ->withSession(assignmentConfirmedSession())
        ->post(route('back-office.organization.assignments.store', $position), [
            'user_id' => $firstHolder->getKey(),
        ])
        ->assertRedirect();

    $firstAssignment = PositionAssignment::query()->active()->firstOrFail();

    $this->actingAs($manager)
        ->withSession(assignmentConfirmedSession())
        ->post(route('back-office.organization.assignments.replace', $position), [
            'user_id' => $secondHolder->getKey(),
        ])
        ->assertRedirect();

    $secondAssignment = PositionAssignment::query()->active()->firstOrFail();

    $this->actingAs($manager)
        ->withSession(assignmentConfirmedSession())
        ->patch(route('back-office.organization.assignments.end', $secondAssignment))
        ->assertRedirect();

    expect($firstAssignment->fresh()->ended_at)->not->toBeNull()
        ->and($secondAssignment->fresh()->ended_at)->not->toBeNull()
        ->and(PositionAssignment::query()->active()->count())->toBe(0)
        ->and(AuditLog::query()
            ->where('actor_user_id', $manager->getKey())
            ->whereIn('action', [
                AuditAction::PositionAssigned->value,
                AuditAction::PositionHolderReplaced->value,
                AuditAction::PositionAssignmentEnded->value,
            ])
            ->count())->toBe(3);
});

test('assignment lifecycle conflicts return 409 without corrupting active holder', function (): void {
    $manager = positionAssignmentManager();
    $position = assignmentPosition();
    $holder = User::factory()->internal()->create();

    $this->actingAs($manager)
        ->withSession(assignmentConfirmedSession())
        ->post(route('back-office.organization.assignments.store', $position), ['user_id' => $holder->getKey()]);

    $this->actingAs($manager)
        ->withSession(assignmentConfirmedSession())
        ->postJson(route('back-office.organization.assignments.store', $position), ['user_id' => User::factory()->internal()->create()->getKey()])
        ->assertConflict();

    $this->actingAs($manager)
        ->withSession(assignmentConfirmedSession())
        ->postJson(route('back-office.organization.assignments.replace', $position), ['user_id' => $holder->getKey()])
        ->assertConflict();

    expect(PositionAssignment::query()->active()->count())->toBe(1)
        ->and(PositionAssignment::query()->active()->firstOrFail()->user_id)->toBe($holder->getKey());
});

test('assignment input rejects unverified users and server owned timestamps', function (): void {
    $manager = positionAssignmentManager();
    $position = assignmentPosition();
    $unverified = User::factory()->internal()->unverified()->create();

    $this->actingAs($manager)
        ->withSession(assignmentConfirmedSession())
        ->post(route('back-office.organization.assignments.store', $position), [
            'user_id' => $unverified->getKey(),
            'started_at' => now()->subYear()->toISOString(),
        ])
        ->assertInvalid(['user_id', 'started_at']);

    expect(PositionAssignment::query()->exists())->toBeFalse();
});

test('assignment mutations require their dedicated permission and recent password', function (): void {
    $position = assignmentPosition();
    $holder = User::factory()->internal()->create();
    $viewer = User::factory()->internal()->withTwoFactor()->create();
    $role = Role::findOrCreate('organization-view-only', AuthorizationCatalog::GUARD_NAME);
    $role->givePermissionTo(Permission::findOrCreate(PermissionName::ViewOrganization->value));
    $viewer->assignRole($role);

    $this->actingAs($viewer)
        ->withSession(assignmentConfirmedSession())
        ->post(route('back-office.organization.assignments.store', $position), ['user_id' => $holder->getKey()])
        ->assertForbidden();

    $manager = positionAssignmentManager();

    $this->actingAs($manager)
        ->withSession(['auth.password_confirmed_at' => 0])
        ->post(route('back-office.organization.assignments.store', $position), ['user_id' => $holder->getKey()])
        ->assertRedirect(route('back-office.password.confirm'));
});

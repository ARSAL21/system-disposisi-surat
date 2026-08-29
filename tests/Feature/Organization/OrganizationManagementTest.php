<?php

use App\Actions\SynchronizePositionLevelCatalog;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\OrganizationalUnit;
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

function organizationUser(PermissionName ...$permissions): User
{
    $user = User::factory()->internal()->withTwoFactor()->create();
    $role = Role::findOrCreate('organization-test-'.str()->random(8), AuthorizationCatalog::GUARD_NAME);
    $role->syncPermissions(array_map(
        fn (PermissionName $permission): Permission => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ),
        $permissions,
    ));
    $user->assignRole($role);

    return $user;
}

function organizationConfirmedSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

test('organization workspace enforces account and explicit view boundaries', function (): void {
    $public = User::factory()->create();
    $viewer = organizationUser(PermissionName::ViewOrganization);

    $this->actingAs($public)
        ->get(route('back-office.organization.structure.index'))
        ->assertNotFound();

    $this->actingAs(User::factory()->internal()->withTwoFactor()->create())
        ->get(route('back-office.organization.structure.index'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(route('back-office.organization.structure.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/organization/structure/Index')
            ->has('levels', 4)
            ->where('mutationSecurity.can_manage', false)
            ->where('mutationSecurity.can_mutate', false));
});

test('organization mutations require permission MFA and recent password', function (): void {
    $viewer = organizationUser(PermissionName::ViewOrganization);

    $this->actingAs($viewer)
        ->withSession(organizationConfirmedSession())
        ->post(route('back-office.organization.units.store'), ['name' => 'Bagian Umum'])
        ->assertForbidden();

    $withoutMfa = User::factory()->internal()->create();
    $role = Role::findOrCreate('organization-manager-without-mfa', AuthorizationCatalog::GUARD_NAME);
    $role->givePermissionTo(Permission::findOrCreate(PermissionName::ManageOrganization->value));
    $withoutMfa->assignRole($role);

    $this->actingAs($withoutMfa)
        ->withSession(organizationConfirmedSession())
        ->post(route('back-office.organization.units.store'), ['name' => 'Bagian Umum'])
        ->assertRedirect(route('security.edit'));

    $manager = organizationUser(PermissionName::ViewOrganization, PermissionName::ManageOrganization);

    $this->actingAs($manager)
        ->withSession(['auth.password_confirmed_at' => 0])
        ->post(route('back-office.organization.units.store'), ['name' => 'Bagian Umum'])
        ->assertRedirect(route('back-office.password.confirm'));
});

test('manager creates and updates units and positions with atomic audits', function (): void {
    $manager = organizationUser(PermissionName::ViewOrganization, PermissionName::ManageOrganization);

    $this->actingAs($manager)
        ->withSession(organizationConfirmedSession())
        ->post(route('back-office.organization.units.store'), [
            'code' => 'bagian_umum',
            'name' => 'Bagian Umum',
            'parent_id' => null,
        ])
        ->assertRedirect();

    $unit = OrganizationalUnit::query()->firstOrFail();
    $level = PositionLevel::query()->where('code', 'GENERAL_AFFAIRS')->firstOrFail();

    $this->actingAs($manager)
        ->withSession(organizationConfirmedSession())
        ->post(route('back-office.organization.positions.store'), [
            'position_level_id' => $level->getKey(),
            'organizational_unit_id' => $unit->getKey(),
            'code' => 'operator_umum',
            'name' => 'Operator Bagian Umum',
        ])
        ->assertRedirect();

    $position = Position::query()->firstOrFail();

    expect($unit->code)->toBe('BAGIAN_UMUM')
        ->and($position->code)->toBe('OPERATOR_UMUM')
        ->and($position->position_level_id)->toBe($level->getKey())
        ->and(AuditLog::query()->where('actor_user_id', $manager->getKey())->count())->toBe(2);

    $this->actingAs($manager)
        ->withSession(organizationConfirmedSession())
        ->patch(route('back-office.organization.positions.update', $position), [
            'name' => 'Nama Baru',
            'organizational_unit_id' => null,
            'code' => 'ATTEMPTED_CHANGE',
        ])
        ->assertInvalid('code');

    expect($position->fresh()->code)->toBe('OPERATOR_UMUM');
});

test('unit hierarchy rejects cycles and guarded deactivation conflicts', function (): void {
    $manager = organizationUser(PermissionName::ViewOrganization, PermissionName::ManageOrganization);
    $root = new OrganizationalUnit;
    $root->code = 'ROOT';
    $root->name = 'Root';
    $root->is_active = true;
    $root->save();
    $child = new OrganizationalUnit;
    $child->parent_id = $root->getKey();
    $child->code = 'CHILD';
    $child->name = 'Child';
    $child->is_active = true;
    $child->save();

    $this->actingAs($manager)
        ->withSession(organizationConfirmedSession())
        ->patchJson(route('back-office.organization.units.update', $root), [
            'name' => $root->name,
            'parent_id' => $child->getKey(),
        ])
        ->assertUnprocessable();

    $this->actingAs($manager)
        ->withSession(organizationConfirmedSession())
        ->patchJson(route('back-office.organization.units.status', $root), ['is_active' => false])
        ->assertConflict();

    expect($root->fresh()->is_active)->toBeTrue();
});

test('position with an active officeholder cannot be deactivated', function (): void {
    $manager = organizationUser(PermissionName::ViewOrganization, PermissionName::ManageOrganization);
    $level = PositionLevel::query()->where('code', 'GENERAL_AFFAIRS')->firstOrFail();
    $position = new Position;
    $position->position_level_id = $level->getKey();
    $position->code = 'GENERAL_OPERATOR';
    $position->name = 'General Operator';
    $position->is_active = true;
    $position->save();
    $holder = User::factory()->internal()->create();
    $assignment = new PositionAssignment;
    $assignment->position_id = $position->getKey();
    $assignment->user_id = $holder->getKey();
    $assignment->started_at = now()->subDay();
    $assignment->assigned_by_user_id = $manager->getKey();
    $assignment->save();

    $this->actingAs($manager)
        ->withSession(organizationConfirmedSession())
        ->patchJson(route('back-office.organization.positions.status', $position), ['is_active' => false])
        ->assertConflict();

    expect($position->fresh()->is_active)->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::PositionStatusChanged->value)->exists())->toBeFalse();
});

<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function privilegeAuditViewer(?User $user = null): User
{
    $user ??= User::factory()->internal()->create();
    $permission = Permission::findOrCreate(
        PermissionName::ViewPrivilegeAudits->value,
        AuthorizationCatalog::GUARD_NAME,
    );
    $role = Role::findOrCreate('privilege-auditor', AuthorizationCatalog::GUARD_NAME);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    return $user;
}

/** @param array<string, mixed> $overrides */
function createPrivilegeAudit(array $overrides = []): AuditLog
{
    $audit = new AuditLog;
    $audit->actor_user_id = $overrides['actor_user_id'] ?? null;
    $audit->actor_position_assignment_id = null;
    $audit->action = $overrides['action'] ?? AuditAction::RoleChanged->value;
    $audit->subject_type = $overrides['subject_type'] ?? 'role';
    $audit->subject_id = $overrides['subject_id'] ?? null;
    $audit->old_values = $overrides['old_values'] ?? null;
    $audit->new_values = $overrides['new_values'] ?? null;
    $audit->metadata = $overrides['metadata'] ?? ['source' => 'console'];
    $audit->request_id = $overrides['request_id'] ?? fake()->uuid();
    $audit->ip_address = $overrides['ip_address'] ?? null;
    $audit->user_agent = $overrides['user_agent'] ?? null;
    $audit->created_at = $overrides['created_at'] ?? Date::now();
    $audit->save();

    return $audit;
}

test('privilege audit route enforces portal and explicit permission boundaries', function (): void {
    expect(route('back-office.privilege-audits.index', absolute: false))
        ->toBe('/back-office/audits/privileges');

    $this->get(route('back-office.privilege-audits.index'))
        ->assertRedirect(route('back-office.login'));

    $publicUser = User::factory()->create();
    privilegeAuditViewer($publicUser);

    $this->actingAs($publicUser)
        ->get(route('back-office.privilege-audits.index'))
        ->assertNotFound();

    $this->actingAs(User::factory()->internal()->create())
        ->get(route('back-office.privilege-audits.index'))
        ->assertForbidden();

    $viewer = privilegeAuditViewer();

    $this->actingAs($viewer)
        ->get(route('back-office.privilege-audits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/privilege-audits/Index')
            ->where('auth.capabilities.can_view_privilege_audits', true)
            ->where('routes.index', route('back-office.privilege-audits.index'))
            ->has('filterOptions.actions', 3)
            ->has('audits.data', 0));
});

test('privilege audit response is action scoped sanitized and uses deleted target snapshots', function (): void {
    $viewer = privilegeAuditViewer();
    $role = Role::findOrCreate('operator-lama', AuthorizationCatalog::GUARD_NAME);

    createPrivilegeAudit([
        'actor_user_id' => $viewer->getKey(),
        'subject_id' => $role->getKey(),
        'old_values' => [
            'name' => $role->name,
            'guard_name' => 'web',
            'permissions' => ['authorization.view'],
            'password' => 'tidak-boleh-bocor',
            'two_factor_secret' => 'tidak-boleh-bocor',
        ],
        'metadata' => [
            'source' => 'web',
            'change' => 'role_deleted',
            'command' => 'command:rahasia',
            'token' => 'tidak-boleh-bocor',
        ],
        'ip_address' => '192.168.10.14',
        'user_agent' => str_repeat('A', 600),
    ]);
    $role->delete();

    createPrivilegeAudit([
        'action' => AuditAction::PositionAssigned->value,
        'subject_type' => 'position_assignment',
        'metadata' => ['source' => 'web'],
    ]);
    createPrivilegeAudit([
        'action' => AuditAction::RoleChanged->value,
        'subject_type' => 'position_assignment',
        'metadata' => ['source' => 'web'],
    ]);

    $this->actingAs($viewer)
        ->get(route('back-office.privilege-audits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('audits.data', 1)
            ->where('audits.data.0.action', AuditAction::RoleChanged->value)
            ->where('audits.data.0.actor.kind', 'user')
            ->where('audits.data.0.target.label', 'operator-lama')
            ->where('audits.data.0.target.secondary', 'Snapshot audit')
            ->where('audits.data.0.target.exists', false)
            ->where('audits.data.0.command', null)
            ->where('audits.data.0.ip_address', '192.168.10.14')
            ->where('audits.data.0.user_agent', fn (string $value): bool => strlen($value) === 500)
            ->missing('audits.data.0.before.password')
            ->missing('audits.data.0.before.two_factor_secret')
            ->missing('audits.data.0.metadata'));
});

test('privilege audits provide server side filters summaries and pagination', function (): void {
    $viewer = privilegeAuditViewer();
    $webTarget = User::factory()->internal()->create(['name' => 'Target Web']);
    $consoleTarget = User::factory()->internal()->create(['name' => 'Target Console']);

    foreach (range(1, 17) as $index) {
        createPrivilegeAudit([
            'actor_user_id' => $viewer->getKey(),
            'action' => AuditAction::RoleChanged->value,
            'subject_type' => 'user',
            'subject_id' => $webTarget->getKey(),
            'new_values' => ['roles' => ["role-{$index}"]],
            'metadata' => ['source' => 'web', 'change' => 'user_roles_synchronized'],
            'created_at' => Date::parse('2026-08-20 08:00:00')->addMinutes($index),
        ]);
    }

    createPrivilegeAudit([
        'actor_user_id' => $viewer->getKey(),
        'action' => AuditAction::RoleChanged->value,
        'subject_type' => 'user',
        'subject_id' => $webTarget->getKey(),
        'new_values' => ['roles' => ['role-batas-wita']],
        'metadata' => ['source' => 'web', 'change' => 'user_roles_synchronized'],
        'created_at' => Date::parse('2026-08-19 17:00:00'),
    ]);

    foreach (range(1, 3) as $index) {
        createPrivilegeAudit([
            'action' => AuditAction::InternalAccountProvisioned->value,
            'subject_type' => 'user',
            'subject_id' => $consoleTarget->getKey(),
            'new_values' => ['name' => $consoleTarget->name, 'email' => $consoleTarget->email],
            'metadata' => ['source' => 'console', 'command' => 'internal:user'],
            'created_at' => Date::parse('2026-08-21 09:00:00')->addMinutes($index),
        ]);
    }

    $this->actingAs($viewer)
        ->get(route('back-office.privilege-audits.index', [
            'action' => AuditAction::RoleChanged->value,
            'source' => 'web',
            'actor' => $viewer->email,
            'target_type' => 'user',
            'target' => 'Target Web',
            'date_from' => '2026-08-20',
            'date_to' => '2026-08-20',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', 18)
            ->where('summary.web', 18)
            ->where('summary.console', 0)
            ->where('audits.meta.current_page', 1)
            ->where('audits.meta.last_page', 2)
            ->where('audits.meta.per_page', 15)
            ->where('audits.meta.total', 18)
            ->has('audits.data', 15));

    $this->actingAs($viewer)
        ->get(route('back-office.privilege-audits.index', [
            'source' => 'console',
            'actor' => 'sistem',
            'target' => 'Target Console',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', 3)
            ->where('summary.console', 3)
            ->has('audits.data', 3)
            ->where('audits.data.0.actor.kind', 'system'));

    $this->actingAs($viewer)
        ->getJson(route('back-office.privilege-audits.index', [
            'date_from' => '2026-08-22',
            'date_to' => '2026-08-20',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date_to');
});

test('privilege audits remain append only and expose no mutation endpoint', function (): void {
    $viewer = privilegeAuditViewer();
    $audit = createPrivilegeAudit();

    expect(fn () => $audit->forceFill(['action' => AuditAction::PermissionChanged->value])->save())
        ->toThrow(LogicException::class);

    $audit = $audit->fresh();

    expect(fn () => $audit->delete())->toThrow(LogicException::class);

    $this->actingAs($viewer)
        ->patchJson('/back-office/audits/privileges/'.$audit->getKey(), [])
        ->assertNotFound();

    $this->actingAs($viewer)
        ->deleteJson('/back-office/audits/privileges/'.$audit->getKey())
        ->assertNotFound();
});

<?php

use App\Actions\SynchronizeAuthorizationCatalog;
use App\Enums\RoleName;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(SynchronizeAuthorizationCatalog::class)->execute();
});

function superAdmin(): User
{
    $admin = User::factory()->internal()->withTwoFactor()->create();
    $admin->assignRole(RoleName::SuperAdmin->value);

    return $admin;
}

test('super admin can deactivate an active user, clearing sessions and detaching roles', function (): void {
    $admin = superAdmin();
    $target = User::factory()->internal()->create();
    $target->assignRole(RoleName::LetterOfficer->value);

    // Create a mock active session in database
    DB::table('sessions')->insert([
        'id' => 'test-session-target-id',
        'user_id' => $target->id,
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'payload' => 'dummy',
        'last_activity' => time(),
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('back-office.users.status.update', $target), [
            'is_active' => false,
            'reason' => 'Penonaktifan akun sementara untuk audit internal.',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $target->refresh();
    expect($target->is_active)->toBeFalse()
        ->and($target->roles)->toBeEmpty()
        ->and(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);

    // Audit events
    $deactivatedEvent = UserAccountEvent::where('user_id', $target->id)
        ->where('event_type', UserAccountEventType::UserDeactivated)
        ->first();
    expect($deactivatedEvent)->not->toBeNull()
        ->and($deactivatedEvent->actor_user_id)->toBe($admin->id)
        ->and($deactivatedEvent->reason)->toBe('Penonaktifan akun sementara untuk audit internal.');

    $rolesDetachedEvent = UserAccountEvent::where('user_id', $target->id)
        ->where('event_type', UserAccountEventType::RolesDetached)
        ->first();
    expect($rolesDetachedEvent)->not->toBeNull();
});

test('deactivation requires a valid reason of at least 10 characters', function (): void {
    $admin = superAdmin();
    $target = User::factory()->internal()->create();

    $this->actingAs($admin)
        ->patch(route('back-office.users.status.update', $target), [
            'is_active' => false,
            'reason' => 'pendek',
        ])
        ->assertSessionHasErrors(['reason']);
});

test('user cannot deactivate themselves', function (): void {
    $admin = superAdmin();

    $this->actingAs($admin)
        ->patch(route('back-office.users.status.update', $admin), [
            'is_active' => false,
            'reason' => 'Mencoba menonaktifkan akun sendiri.',
        ])
        ->assertForbidden();
});

test('super admin cannot deactivate another super admin via user management', function (): void {
    $admin = superAdmin();
    $otherAdmin = superAdmin();

    $this->actingAs($admin)
        ->patch(route('back-office.users.status.update', $otherAdmin), [
            'is_active' => false,
            'reason' => 'Mencoba menonaktifkan akun super admin lain.',
        ])
        ->assertForbidden();
});

test('internal user holding an active position assignment cannot be deactivated (returns HTTP 409 Conflict)', function (): void {
    $admin = superAdmin();
    $target = PositionAssignmentTestData::internalUser();
    $position = PositionAssignmentTestData::position();
    PositionAssignmentTestData::assignment($target, $position, $admin);

    $response = $this->actingAs($admin)
        ->patch(route('back-office.users.status.update', $target), [
            'is_active' => false,
            'reason' => 'Penonaktifan akun yang masih memegang jabatan aktif.',
        ]);

    $response->assertStatus(409);
    expect($target->fresh()->is_active)->toBeTrue();
});

test('reactivating an inactive user succeeds without automatically restoring detached roles', function (): void {
    $admin = superAdmin();
    $target = User::factory()->internal()->create(['is_active' => false]);

    $response = $this->actingAs($admin)
        ->patch(route('back-office.users.status.update', $target), [
            'is_active' => true,
            'reason' => 'Pengaktifan kembali akun setelah cuti.',
        ]);

    $response->assertRedirect();

    $target->refresh();
    expect($target->is_active)->toBeTrue()
        ->and($target->roles)->toBeEmpty(); // Least privilege: roles remain empty

    $reactivatedEvent = UserAccountEvent::where('user_id', $target->id)
        ->where('event_type', UserAccountEventType::UserReactivated)
        ->first();
    expect($reactivatedEvent)->not->toBeNull()
        ->and($reactivatedEvent->actor_user_id)->toBe($admin->id);
});

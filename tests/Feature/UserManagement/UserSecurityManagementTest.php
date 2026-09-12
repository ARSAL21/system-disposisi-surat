<?php

use App\Actions\SynchronizeAuthorizationCatalog;
use App\Enums\RoleName;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(SynchronizeAuthorizationCatalog::class)->execute();
});

function securitySuperAdmin(): User
{
    $admin = User::factory()->internal()->withTwoFactor()->create();
    $admin->assignRole(RoleName::SuperAdmin->value);

    return $admin;
}

function passwordConfirmedSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

test('super admin can revoke all active sessions for a target user', function (): void {
    $admin = securitySuperAdmin();
    $target = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'target-session-1',
        'user_id' => $target->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'payload1',
        'last_activity' => time(),
    ]);

    $this->actingAs($admin)
        ->post(route('back-office.users.security.sessions.revoke', $target))
        ->assertRedirect();

    expect(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);

    $event = UserAccountEvent::where('user_id', $target->id)
        ->where('event_type', UserAccountEventType::SessionsRevoked)
        ->first();
    expect($event)->not->toBeNull()
        ->and($event->actor_user_id)->toBe($admin->id);
});

test('super admin can trigger password reset link to user email', function (): void {
    Notification::fake();
    $admin = securitySuperAdmin();
    $target = User::factory()->create(['email' => 'target.user@example.com']);

    $this->actingAs($admin)
        ->post(route('back-office.users.security.password-reset', $target))
        ->assertRedirect();

    Notification::assertSentTo($target, ResetPassword::class);

    $event = UserAccountEvent::where('user_id', $target->id)
        ->where('event_type', UserAccountEventType::PasswordResetLinkSent)
        ->first();
    expect($event)->not->toBeNull()
        ->and($event->actor_user_id)->toBe($admin->id);
});

test('super admin can reset MFA for user requiring recent password confirmation', function (): void {
    $admin = securitySuperAdmin();
    $target = User::factory()->withTwoFactor()->create();
    expect($target->hasEnabledTwoFactorAuthentication())->toBeTrue();

    DB::table('sessions')->insert([
        'id' => 'target-mfa-session',
        'user_id' => $target->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'payload2',
        'last_activity' => time(),
    ]);

    // Attempt without password confirmation -> redirects to password confirmation
    $this->actingAs($admin)
        ->post(route('back-office.users.security.two-factor.reset', $target))
        ->assertRedirect();

    // With password confirmation
    $this->actingAs($admin)
        ->withSession(passwordConfirmedSession())
        ->post(route('back-office.users.security.two-factor.reset', $target))
        ->assertRedirect();

    $target->refresh();
    expect($target->hasEnabledTwoFactorAuthentication())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);

    $event = UserAccountEvent::where('user_id', $target->id)
        ->where('event_type', UserAccountEventType::MfaReset)
        ->first();
    expect($event)->not->toBeNull()
        ->and($event->actor_user_id)->toBe($admin->id);
});

test('super admin cannot manipulate security of another super admin via UI', function (): void {
    $admin = securitySuperAdmin();
    $otherAdmin = securitySuperAdmin();

    $this->actingAs($admin)
        ->post(route('back-office.users.security.sessions.revoke', $otherAdmin))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('back-office.users.security.password-reset', $otherAdmin))
        ->assertForbidden();

    $this->actingAs($admin)
        ->withSession(passwordConfirmedSession())
        ->post(route('back-office.users.security.two-factor.reset', $otherAdmin))
        ->assertForbidden();
});

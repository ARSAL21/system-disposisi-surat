<?php

use App\Actions\SynchronizeAuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\RoleName;
use App\Enums\UserAccountEventType;
use App\Models\User;
use App\Models\UserAccountEvent;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(SynchronizeAuthorizationCatalog::class)->execute();
});

function getSuperAdmin(): User
{
    $admin = User::factory()->internal()->withTwoFactor()->create();
    $admin->assignRole(RoleName::SuperAdmin->value);

    return $admin;
}

test('super admin can issue an invitation with secure token hashing', function (): void {
    $admin = getSuperAdmin();

    $response = $this->actingAs($admin)
        ->post(route('back-office.users.invitations.store'), [
            'name' => 'Dr. Hasan Basri',
            'email' => 'hasan@pemkot.go.id',
            'phone_number' => '081298765432',
            'account_type' => AccountType::InternalAccount->value,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $invitation = UserInvitation::where('normalized_email', 'hasan@pemkot.go.id')->first();
    expect($invitation)->not->toBeNull()
        ->and($invitation->name)->toBe('Dr. Hasan Basri')
        ->and($invitation->phone_number)->toBe('081298765432')
        ->and($invitation->account_type)->toBe(AccountType::InternalAccount)
        ->and(strlen($invitation->token_hash))->toBe(64) // SHA-256 is 64 hex characters
        ->and($invitation->expires_at)->toBeGreaterThan(now());

    // Audit event created
    $event = UserAccountEvent::where('user_invitation_id', $invitation->id)->first();
    expect($event)->not->toBeNull()
        ->and($event->event_type)->toBe(UserAccountEventType::InvitationCreated)
        ->and($event->actor_user_id)->toBe($admin->id);
});

test('cannot create invitation for an already registered email', function (): void {
    $admin = getSuperAdmin();
    User::factory()->create(['email' => 'registered@example.com']);

    $this->actingAs($admin)
        ->post(route('back-office.users.invitations.store'), [
            'name' => 'Registered User',
            'email' => 'registered@example.com',
            'account_type' => AccountType::PublicAccount->value,
        ])
        ->assertSessionHasErrors(['email']);
});

test('reissuing invitation for same email revokes prior pending invitation', function (): void {
    $admin = getSuperAdmin();

    $this->actingAs($admin)
        ->post(route('back-office.users.invitations.store'), [
            'name' => 'Target Person',
            'email' => 'target@example.com',
            'account_type' => AccountType::PublicAccount->value,
        ]);

    $firstInvitation = UserInvitation::where('normalized_email', 'target@example.com')->first();
    expect($firstInvitation->revoked_at)->toBeNull();

    $this->actingAs($admin)
        ->post(route('back-office.users.invitations.store'), [
            'name' => 'Target Person Updated',
            'email' => 'target@example.com',
            'account_type' => AccountType::PublicAccount->value,
        ]);

    expect($firstInvitation->fresh()->revoked_at)->not->toBeNull();
    $secondInvitation = UserInvitation::where('normalized_email', 'target@example.com')
        ->whereNull('revoked_at')
        ->first();
    expect($secondInvitation)->not->toBeNull()
        ->and($secondInvitation->id)->not->toBe($firstInvitation->id);
});

test('super admin can resend and revoke invitation', function (): void {
    $admin = getSuperAdmin();

    $this->actingAs($admin)
        ->post(route('back-office.users.invitations.store'), [
            'name' => 'Staff Humas',
            'email' => 'humas@pemkot.go.id',
            'account_type' => AccountType::InternalAccount->value,
        ]);

    $invitation = UserInvitation::where('normalized_email', 'humas@pemkot.go.id')->first();
    $oldHash = $invitation->token_hash;

    // Resend
    $this->actingAs($admin)
        ->post(route('back-office.users.invitations.resend', $invitation))
        ->assertRedirect();

    $invitation->refresh();
    expect($invitation->token_hash)->not->toBe($oldHash);

    // Revoke
    $this->actingAs($admin)
        ->post(route('back-office.users.invitations.revoke', $invitation), [
            'reason' => 'Dibatalkan oleh bagian kepegawaian',
        ])
        ->assertRedirect();

    expect($invitation->fresh()->revoked_at)->not->toBeNull();
});

test('recipient can accept invitation and activate account without admin knowing password', function (): void {
    $admin = getSuperAdmin();
    $rawToken = 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';

    $invitation = UserInvitation::create([
        'public_id' => '01J7ABCDEF1234567890123456',
        'invited_by_user_id' => $admin->id,
        'normalized_email' => 'calon.pegawai@pemkot.go.id',
        'name' => 'Calon Pegawai',
        'phone_number' => '081345678901',
        'account_type' => AccountType::InternalAccount,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHours(48),
    ]);

    // View acceptance page with valid token
    $this->get(route('account-invitations.show', ['publicId' => $invitation->public_id, 'token' => $rawToken]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/invitations/Accept')
            ->has('invitation', fn (Assert $inv) => $inv
                ->where('public_id', $invitation->public_id)
                ->where('email', 'calon.pegawai@pemkot.go.id')
                ->where('status', 'PENDING')
                ->etc()
            )
        );

    // View acceptance page with invalid token
    $this->get(route('account-invitations.show', ['publicId' => $invitation->public_id, 'token' => 'invalid-token']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/invitations/Accept')
            ->where('invitation.status', 'INVALID')
        );

    // Accept invitation with valid new password
    $response = $this->post(route('account-invitations.accept', $invitation->public_id), [
        'token' => $rawToken,
        'name' => 'Calon Pegawai Lengkap',
        'phone_number' => '081345678901',
        'password' => 'P@ssword123#Secure!',
        'password_confirmation' => 'P@ssword123#Secure!',
    ]);

    $response->assertRedirect(route('back-office.dashboard'));

    $newUser = User::where('email', 'calon.pegawai@pemkot.go.id')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->is_active)->toBeTrue()
        ->and($newUser->email_verified_at)->not->toBeNull()
        ->and($newUser->account_type)->toBe(AccountType::InternalAccount)
        ->and(Hash::check('P@ssword123#Secure!', $newUser->password))->toBeTrue();

    expect($invitation->fresh()->accepted_at)->not->toBeNull();

    // Authenticated into system
    $this->assertAuthenticatedAs($newUser);
});

<?php

use App\Actions\SynchronizeAuthorizationCatalog;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(SynchronizeAuthorizationCatalog::class)->execute();
});

function makeSuperAdmin(): User
{
    $admin = User::factory()->internal()->withTwoFactor()->create();
    $admin->assignRole(RoleName::SuperAdmin->value);

    return $admin;
}

test('guest cannot access user management index', function (): void {
    $this->get(route('back-office.users.index'))
        ->assertRedirect(route('back-office.login'));
});

test('public user cannot access back-office user management', function (): void {
    $publicUser = User::factory()->create();

    $this->actingAs($publicUser)
        ->get(route('back-office.users.index'))
        ->assertNotFound();
});

test('internal user without users.view permission receives forbidden', function (): void {
    $officer = User::factory()->internal()->create();
    $officer->assignRole(RoleName::LetterOfficer->value);

    $this->actingAs($officer)
        ->get(route('back-office.users.index'))
        ->assertForbidden();
});

test('super admin can view user management catalogue', function (): void {
    $admin = makeSuperAdmin();
    $internalUser = User::factory()->internal()->create(['name' => 'Internal Pegawai']);
    $publicUser = User::factory()->create(['name' => 'Warga Publik']);

    $this->actingAs($admin)
        ->get(route('back-office.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/users/Index')
            ->has('users', 3) // admin + internalUser + publicUser
            ->has('capabilities', fn (Assert $cap) => $cap
                ->where('can_view_users', true)
                ->where('can_invite_users', true)
                ->where('can_manage_user_status', true)
                ->where('can_manage_user_security', true)
            )
            ->where('preview', false)
        );
});

test('super admin can view user detail with zero leakage metrics', function (): void {
    $admin = makeSuperAdmin();
    $target = User::factory()->create([
        'name' => 'Budi Sudarsono',
        'email' => 'budi@example.com',
        'phone_number' => '081234567890',
    ]);

    $this->actingAs($admin)
        ->get(route('back-office.users.show', $target))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/users/Show')
            ->has('user', fn (Assert $user) => $user
                ->where('id', $target->id)
                ->where('name', 'Budi Sudarsono')
                ->where('email', 'budi@example.com')
                ->where('phone_number', '081234567890')
                ->where('account_type', 'PUBLIC')
                ->has('submission_metrics', fn (Assert $metrics) => $metrics
                    ->where('total', 0)
                    ->where('draft', 0)
                    ->where('submitted', 0)
                    ->where('verified', 0)
                    ->where('rejected', 0)
                )
                ->etc()
            )
            ->where('preview', false)
        );
});

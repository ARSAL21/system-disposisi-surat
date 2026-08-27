<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Tests\TestCase;

class BackOfficeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_back_office_entry_redirects_guests_to_the_internal_login(): void
    {
        $this->get(route('back-office.entry'))
            ->assertRedirect(route('back-office.login'));
    }

    public function test_back_office_login_route_renders_the_internal_login_page(): void
    {
        $this->get(route('back-office.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('back-office/auth/Login')
                ->has('canResetPassword')
                ->has('status'),
            );
    }

    public function test_public_accounts_cannot_discover_the_back_office_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('back-office.entry'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('back-office.login'))
            ->assertNotFound();
    }

    public function test_internal_accounts_are_redirected_from_the_entry_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->internal()->create())
            ->get(route('back-office.entry'))
            ->assertRedirect(route('back-office.dashboard'));
    }

    public function test_guests_requesting_a_protected_back_office_route_use_the_internal_login(): void
    {
        $this->get(route('back-office.dashboard'))
            ->assertRedirect(route('back-office.login'));
    }

    public function test_internal_accounts_can_authenticate_only_through_the_back_office_login(): void
    {
        $user = User::factory()->internal()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post(route('back-office.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_public_accounts_cannot_authenticate_through_the_back_office_login(): void
    {
        $user = User::factory()->create();

        $this->post(route('back-office.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_internal_accounts_cannot_authenticate(): void
    {
        $user = User::factory()->internal()->inactive()->create();

        $this->post(route('back-office.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_public_and_back_office_password_logins_share_the_same_rate_limit_budget(): void
    {
        $user = User::factory()->internal()->create();

        foreach (range(1, 5) as $_attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('back-office.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_internal_two_factor_accounts_are_sent_to_the_shared_challenge(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->internal()->withTwoFactor()->create();

        $this->post(route('back-office.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHas('login.id', $user->id);

        $this->assertGuest();
    }

    public function test_passkey_authorization_is_scoped_to_the_login_portal(): void
    {
        $this->skipUnlessFortifyHas(Features::passkeys());

        $publicUser = User::factory()->create();
        $internalUser = User::factory()->internal()->create();
        $inactiveInternalUser = User::factory()->internal()->inactive()->create();

        $publicRequest = $this->requestForRoute('passkey.login');
        $backOfficeRequest = $this->requestForRoute('back-office.passkey.login');

        $this->assertTrue(Passkeys::allowsLogin($publicRequest, $this->passkeyFor($publicUser)));
        $this->assertFalse(Passkeys::allowsLogin($publicRequest, $this->passkeyFor($internalUser)));
        $this->assertTrue(Passkeys::allowsLogin($backOfficeRequest, $this->passkeyFor($internalUser)));
        $this->assertFalse(Passkeys::allowsLogin($backOfficeRequest, $this->passkeyFor($publicUser)));
        $this->assertFalse(Passkeys::allowsLogin($backOfficeRequest, $this->passkeyFor($inactiveInternalUser)));
    }

    private function requestForRoute(string $name): Request
    {
        $route = app('router')->getRoutes()->getByName($name);

        $this->assertInstanceOf(Route::class, $route);

        $request = Request::create('/'.$route->uri(), 'POST');
        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }

    private function passkeyFor(User $user): Passkey
    {
        $passkey = new Passkey;
        $passkey->setRelation('user', $user);

        return $passkey;
    }
}

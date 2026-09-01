<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_route_redirects_to_settings_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertRedirect(route('profile.edit'));
    }

    public function test_password_confirmation_requires_authentication()
    {
        $response = $this->get(route('password.confirm'));

        $response->assertRedirect(route('login'));
    }

    public function test_back_office_password_confirmation_redirects_back_to_current_page()
    {
        $user = User::factory()->internal()->create([
            'password' => bcrypt('secret-password-123'),
        ]);

        $rolesPage = route('back-office.authorization.index');

        $response = $this->actingAs($user)
            ->from($rolesPage)
            ->post(route('back-office.password.confirm.store'), [
                'password' => 'secret-password-123',
            ]);

        $response->assertRedirect($rolesPage);
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_back_office_password_confirmation_honors_intended_url_if_present()
    {
        $user = User::factory()->internal()->create([
            'password' => bcrypt('secret-password-123'),
        ]);

        $intendedUrl = route('back-office.organization.structure.index');

        $response = $this->actingAs($user)
            ->withSession(['url.intended' => $intendedUrl])
            ->post(route('back-office.password.confirm.store'), [
                'password' => 'secret-password-123',
            ]);

        $response->assertRedirect($intendedUrl);
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }
}

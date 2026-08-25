<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@gmail.com')->firstOrFail();

        $this->assertSame(AccountType::PublicAccount, $user->account_type);
        $this->assertTrue($user->is_active);
    }

    public function test_public_registration_cannot_select_internal_account_context()
    {
        $this->post(route('register.store'), [
            'name' => 'Untrusted User',
            'email' => 'untrusted@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'account_type' => AccountType::InternalAccount->value,
            'is_active' => false,
            'is_admin' => true,
            'role' => 'system-administrator',
            'position' => 'mayor',
        ]);

        $user = User::query()->where('email', 'untrusted@gmail.com')->firstOrFail();

        $this->assertSame(AccountType::PublicAccount, $user->account_type);
        $this->assertTrue($user->is_active);
    }
}

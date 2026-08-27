<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_users_can_access_only_the_public_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('public.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('back-office.dashboard'))
            ->assertNotFound();
    }

    public function test_internal_users_can_access_only_the_internal_area(): void
    {
        $user = User::factory()->internal()->create();

        $this->actingAs($user)
            ->get(route('back-office.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('back-office/Dashboard'),
            );

        $this->actingAs($user)
            ->get(route('public.dashboard'))
            ->assertNotFound();
    }

    public function test_inactive_authenticated_users_cannot_access_protected_areas(): void
    {
        $publicUser = User::factory()->inactive()->create();
        $internalUser = User::factory()->internal()->inactive()->create();

        $this->actingAs($publicUser)
            ->get(route('public.dashboard'))
            ->assertForbidden();

        $this->actingAs($internalUser)
            ->get(route('back-office.dashboard'))
            ->assertForbidden();
    }

    public function test_legacy_internal_area_is_not_exposed(): void
    {
        $this->actingAs(User::factory()->internal()->create())
            ->get('/internal/dashboard')
            ->assertNotFound();
    }
}

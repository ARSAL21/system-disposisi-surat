<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->get(route('internal.dashboard'))
            ->assertNotFound();
    }

    public function test_internal_users_can_access_only_the_internal_area(): void
    {
        $user = User::factory()->internal()->create();

        $this->actingAs($user)
            ->get(route('internal.dashboard'))
            ->assertOk();

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
            ->get(route('internal.dashboard'))
            ->assertForbidden();
    }
}

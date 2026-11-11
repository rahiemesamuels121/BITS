<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_before_opening_the_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_signed_in_users_can_open_the_dashboard_and_sign_out(): void
    {
        $user = User::factory()->create(['name' => 'Workspace Owner']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('Systems overview')
            ->assertSee('Demo data')
            ->assertSee('Workspace Owner')
            ->assertSee('Sign out');
    }

    public function test_the_homepage_client_login_links_to_the_login_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Client Login');
    }
}

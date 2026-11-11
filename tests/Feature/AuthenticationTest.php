<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Client Login');
    }

    public function test_existing_users_can_sign_in_and_open_the_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $this->post(route('login.store'), [
            'email' => strtoupper($user->email),
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_sign_in_respects_the_intended_destination(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $this->withSession(['url.intended' => route('home')])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_incorrect_passwords_are_rejected_without_flashing_the_password(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'These credentials do not match our records.',
            ])
            ->assertSessionHas('_old_input.email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_invalid_credentials_are_validated(): void
    {
        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'not-an-email',
            'password' => '',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_repeated_failed_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email')
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('email'), 'Too many sign-in attempts.'));

        $this->assertGuest();
    }

    public function test_signing_out_clears_authentication_and_session_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['private-data' => 'only available while signed in'])
            ->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionMissing('private-data')
            ->assertSessionHas('_token');

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_away_from_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_remember_me_sets_the_authentication_cookie(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertCookie(auth()->guard('web')->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_account_command_creates_a_user_with_a_hashed_password_and_role(): void
    {
        $this->seed(RoleSeeder::class);

        $this->artisan('user:create', ['--name' => 'Agency Owner', '--email' => 'OWNER@EXAMPLE.COM', '--role' => Role::AGENCY])
            ->expectsQuestion('Password (at least 12 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-strong-password')
            ->expectsOutputToContain('User created: owner@example.com (Agency).')
            ->assertSuccessful();

        $user = User::query()->where('email', 'owner@example.com')->sole();

        $this->assertTrue(Hash::check('a-strong-password', $user->password));
        $this->assertSame(Role::AGENCY, $user->role->code);
        $this->assertTrue($user->role->users->contains($user));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_the_account_command_requires_matching_password_confirmation(): void
    {
        $this->seed(RoleSeeder::class);

        $this->artisan('user:create', ['--name' => 'Admin', '--email' => 'admin@example.com', '--role' => Role::ADMIN])
            ->expectsQuestion('Password (at least 12 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-different-password')
            ->expectsOutputToContain('confirmation does not match')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_the_account_command_does_not_overwrite_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $originalPassword = $user->password;

        $this->artisan('user:create', ['--name' => 'Another Admin', '--email' => 'admin@example.com', '--role' => Role::ADMIN])
            ->expectsOutputToContain('email has already been taken')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_the_account_command_rejects_unknown_roles(): void
    {
        $this->artisan('user:create', ['--name' => 'Admin', '--email' => 'admin@example.com', '--role' => 'superuser'])
            ->expectsOutputToContain('selected role is invalid')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}

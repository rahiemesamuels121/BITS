<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Forms\FormService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->role()->associate(Role::where('code', $role)->firstOrFail());
        $user->save();

        return $user;
    }

    public function test_only_admins_can_access_and_change_clients(): void
    {
        $this->get(route('clients.index'))->assertRedirect(route('login'));
        $agency = $this->account(Role::AGENCY);
        $this->actingAs($agency)->get(route('clients.index'))->assertForbidden();
        $this->get(route('clients.create'))->assertForbidden();
        $this->post(route('clients.store'), [])->assertForbidden();
        $this->patch(route('clients.status', $agency), ['is_active' => false])->assertForbidden();
        $this->get(route('forms.index'))->assertDontSee('href="'.route('clients.index').'"', false);
    }

    public function test_admin_can_create_a_client_with_a_hashed_password_and_fixed_agency_role(): void
    {
        $admin = $this->account(Role::ADMIN);
        $this->actingAs($admin)->get(route('clients.create'))->assertOk()->assertSee('Create a client');
        $this->post(route('clients.store'), [
            'name' => 'Example Client', 'email' => 'CLIENT@example.com',
            'password' => 'client-password-123', 'password_confirmation' => 'client-password-123',
            'is_active' => true, 'role_id' => $admin->role_id,
        ])->assertRedirect(route('clients.index'));
        $client = User::where('email', 'client@example.com')->sole();
        $this->assertSame(Role::AGENCY, $client->role->code);
        $this->assertTrue($client->is_active);
        $this->assertTrue(Hash::check('client-password-123', $client->password));
        $this->get(route('clients.index'))->assertOk()->assertSee('Example Client')->assertSee('Deactivate');
    }

    public function test_client_creation_validates_email_uniqueness_and_password_confirmation(): void
    {
        $admin = $this->account(Role::ADMIN);
        $this->actingAs($admin)->post(route('clients.store'), [
            'name' => 'Invalid', 'email' => strtoupper($admin->email), 'password' => 'short',
            'password_confirmation' => 'different', 'is_active' => 'unexpected',
        ])->assertSessionHasErrors(['email', 'password', 'is_active'])->assertSessionMissing('_old_input.password');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_deactivate_and_reactivate_clients_but_not_admin_accounts(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $remember = $client->remember_token;
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert(['id' => 'client-existing-session', 'user_id' => $client->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($admin)->patch(route('clients.status', $client), ['is_active' => false])->assertRedirect(route('clients.index'));
        $this->assertFalse($client->fresh()->is_active);
        $this->assertNotSame($remember, $client->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'client-existing-session']);
        $this->patch(route('clients.status', $client), ['is_active' => true])->assertRedirect(route('clients.index'));
        $this->assertTrue($client->fresh()->is_active);
        $this->patch(route('clients.status', $admin), ['is_active' => false])->assertNotFound();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_inactive_clients_cannot_sign_in_but_can_sign_in_after_reactivation(): void
    {
        $client = $this->account(Role::AGENCY);
        $client->password = 'client-password-123';
        $client->is_active = false;
        $client->save();
        $credentials = ['email' => $client->email, 'password' => 'client-password-123'];
        $this->post(route('login.store'), $credentials)->assertSessionHasErrors('email');
        $this->assertGuest();
        $client->is_active = true;
        $client->save();
        $this->post(route('login.store'), $credentials)->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($client);
    }

    public function test_inactive_existing_sessions_are_logged_out_on_the_next_request(): void
    {
        $client = $this->account(Role::AGENCY);
        $this->actingAs($client)->get(route('forms.index'))->assertOk();
        $client->is_active = false;
        $client->save();
        $this->get(route('forms.index'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($client)->getJson(route('forms.index'))->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_clients_can_be_filtered_without_listing_admins(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $client->is_active = false;
        $client->save();
        $this->actingAs($admin)->get(route('clients.index', ['status' => 'inactive', 'q' => $client->email]))->assertOk()->assertSee($client->email)->assertSee('Activate');
        $this->get(route('clients.index', ['status' => 'active']))->assertOk()->assertSee('No clients found.');
    }

    public function test_client_table_includes_filters_and_account_actions(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $this->actingAs($admin)->get(route('clients.index'))->assertOk()
            ->assertSee('<table class="clients-table">', false)->assertSee('Add new client')
            ->assertSee('Search clients')->assertSee('Apply filters')
            ->assertSee(route('clients.edit', $client))->assertSee(route('clients.destroy', $client))
            ->assertSee('Deactivate')->assertSee('Remove')->assertSee('id="fm-confirm"', false);
    }

    public function test_admin_can_edit_clients_without_changing_password_or_role(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $password = $client->password;
        $this->actingAs($admin)->get(route('clients.edit', $client))->assertOk()->assertSee($client->email);
        $this->put(route('clients.update', $client), ['name' => 'Updated client', 'email' => $client->email, 'password' => '', 'role_id' => $admin->role_id, 'is_active' => false])->assertRedirect(route('clients.index'));
        $client->refresh();
        $this->assertSame('Updated client', $client->name);
        $this->assertSame($password, $client->password);
        $this->assertSame(Role::AGENCY, $client->role->code);
        $this->assertTrue($client->is_active);
        $this->put(route('clients.update', $client), ['name' => 'Duplicate', 'email' => $admin->email])->assertSessionHasErrors('email');
    }

    public function test_changing_email_or_password_revokes_existing_client_access(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $remember = $client->remember_token;
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert(['id' => 'edited-client-session', 'user_id' => $client->id, 'payload' => '', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $client->email, 'token' => 'old-reset-token']);
        $this->actingAs($admin)->put(route('clients.update', $client), [
            'name' => $client->name, 'email' => 'updated-client@example.com',
            'password' => 'replacement-password', 'password_confirmation' => 'replacement-password',
        ])->assertRedirect(route('clients.index'));
        $client->refresh();
        $this->assertTrue(Hash::check('replacement-password', $client->password));
        $this->assertSame('updated-client@example.com', $client->email);
        $this->assertNull($client->email_verified_at);
        $this->assertNotSame($remember, $client->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'edited-client-session']);
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_removing_clients_revokes_access_and_retains_their_forms(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $form = app(FormService::class)->create($client, 'Retained client form');
        $this->actingAs($admin)->delete(route('clients.destroy', $client))->assertRedirect(route('clients.index'));
        $this->assertSoftDeleted('users', ['id' => $client->id, 'is_active' => false]);
        $this->assertDatabaseHas('forms', ['id' => $form->id, 'user_id' => $client->id]);
        $this->get(route('clients.index'))->assertDontSee($client->email);
        $this->get(route('clients.edit', $client))->assertNotFound();
        $this->patch(route('clients.status', $client), ['is_active' => true])->assertNotFound();
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $client->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_client_edit_and_removal_cannot_target_admins_or_be_used_by_clients(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $this->actingAs($admin)->get(route('clients.edit', $admin))->assertNotFound();
        $this->put(route('clients.update', $admin), [])->assertNotFound();
        $this->delete(route('clients.destroy', $admin))->assertNotFound();
        $this->actingAs($client)->get(route('clients.edit', $client))->assertForbidden();
        $this->put(route('clients.update', $client), [])->assertForbidden();
        $this->delete(route('clients.destroy', $client))->assertForbidden();
        $this->assertNotSoftDeleted('users', ['id' => $client->id]);
        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }
}

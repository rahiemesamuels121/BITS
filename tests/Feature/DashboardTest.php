<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use App\Services\Forms\FormService;
use Database\Seeders\RoleSeeder;
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
            ->assertViewIs('clients.dashboard')
            ->assertSee('My dashboard')
            ->assertSee('Account details')
            ->assertSee('My contracts')
            ->assertSee('No contract assigned yet.')
            ->assertDontSee('Demo data')
            ->assertSee('Workspace Owner')
            ->assertSee('Sign out');
    }

    public function test_admin_dashboard_counts_real_clients_contracts_and_published_forms(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->for(Role::where('code', 'admin')->sole())->create();
        $agency = Role::where('code', 'agency')->sole();
        User::factory()->count(2)->for($agency)->create();
        User::factory()->for($agency)->create(['is_active' => false]);
        User::factory()->for($agency)->create()->delete();
        User::factory()->create();
        Contract::factory()->count(3)->create();
        Contract::factory()->create(['is_active' => false]);
        $service = app(FormService::class);
        $service->create($admin, 'Published agreement')->update(['status' => 'published']);
        $service->create($admin, 'Draft agreement');
        $service->create($admin, 'Archived agreement')->update(['status' => 'archived']);
        $removed = $service->create($admin, 'Deleted published form');
        $removed->update(['status' => 'published']);
        $removed->delete();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertViewHas('stats', ['active_clients' => 2, 'inactive_clients' => 1, 'active_contracts' => 3, 'inactive_contracts' => 1, 'published_forms' => 1])
            ->assertViewHas('formStatusCounts', ['published' => 1, 'draft' => 1, 'archived' => 1])
            ->assertSee('Client status')->assertSee('Contract status')->assertSee('Form publishing status')
            ->assertSee('66.7%')->assertSee('75.0%')->assertSee('33.3%')
            ->assertSee('Active clients')->assertSee('Inactive contracts')->assertSee('Published agreement')
            ->assertDontSee('Deleted published form')->assertDontSee('Demo data');
    }

    public function test_staff_workspace_dashboard_keeps_its_existing_data_scope_and_permissions(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->for(Role::query()->where('code', Role::STAFF)->sole())->create();
        $other = User::factory()->create();
        Contract::factory()->hasAttached($owner, [], 'clients')->create(['name' => 'My contract']);
        Contract::factory()->create(['name' => 'Private contract']);
        $service = app(FormService::class);
        $service->create($owner, 'My published form')->update(['status' => 'published']);
        $service->create($other, 'Private published form')->update(['status' => 'published']);
        $service->create($owner, 'My draft form');
        $service->create($other, 'Private draft form');
        $service->create($other, 'Private archived form')->update(['status' => 'archived']);
        $this->actingAs($owner)->get(route('dashboard'))->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('stats', ['active_clients' => null, 'inactive_clients' => null, 'active_contracts' => 1, 'inactive_contracts' => 0, 'published_forms' => 1])
            ->assertViewHas('formStatusCounts', ['published' => 1, 'draft' => 1, 'archived' => 0])
            ->assertSee('Contract status')->assertSee('Form publishing status')->assertDontSee('Client status')
            ->assertSee('My contract')->assertSee('My published form')->assertDontSee('Private contract')
            ->assertDontSee('Private published form')->assertDontSee('Active clients')
            ->assertDontSee('href="'.route('clients.index').'"', false)
            ->assertDontSee('href="'.route('contracts.create').'"', false);
        $this->get(route('clients.index'))->assertForbidden();
        $this->get(route('contracts.index'))->assertForbidden();
    }

    public function test_client_dashboard_shows_account_details_and_only_assigned_contract_information(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->for(Role::query()->where('code', Role::AGENCY)->sole())->create([
            'name' => 'Agency Account Owner', 'email' => 'my-account@example.com',
            'created_at' => '2026-10-10 12:00:00',
        ]);
        $other = User::factory()->create(['email' => 'other-client-private@example.com']);
        $form = app(FormService::class)->create($other, 'Contract registration form');
        $contract = Contract::factory()->hasAttached($owner, [], 'clients')->for($form, 'form')->create([
            'name' => 'Work and travel agreement', 'description' => 'My assigned agreement details.',
            'is_active' => true, 'is_indefinite' => false,
            'starts_at' => '2026-11-01', 'ends_at' => '2027-05-31',
        ]);
        Contract::factory()->hasAttached($owner, [], 'clients')->create([
            'name' => 'Indefinite agreement', 'is_active' => false,
        ]);
        Contract::factory()->hasAttached($other, [], 'clients')->create([
            'name' => 'Another client private agreement', 'description' => 'Private unrelated details.',
        ]);
        app(FormService::class)->create($owner, 'My former dashboard form')->update(['status' => 'published']);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()
            ->assertViewIs('clients.dashboard')
            ->assertViewHas('account', fn ($account) => $account->is($owner))
            ->assertViewHas('contracts', fn ($contracts) => $contracts->total() === 2 && $contracts->contains('id', $contract->id))
            ->assertSee($owner->name)->assertSee($owner->email)->assertSee('Agency')
            ->assertSee('Account details')->assertSee('My contracts')->assertSee('Oct 10, 2026')
            ->assertSee('Work and travel agreement')->assertSee('My assigned agreement details.')
            ->assertSee('Nov 1, 2026')->assertSee('May 31, 2027')->assertSee('Contract registration form')
            ->assertSee('Indefinite agreement')->assertSee('Indefinite')->assertSee('Inactive')
            ->assertDontSee('Another client private agreement')->assertDontSee('Private unrelated details.')
            ->assertDontSee($other->email)->assertDontSee($owner->password)->assertDontSee($owner->remember_token)
            ->assertDontSee('Form publishing status')->assertDontSee('My former dashboard form')
            ->assertDontSee('href="'.route('clients.index').'"', false)
            ->assertDontSee('href="'.route('contracts.create').'"', false)
            ->assertDontSee('href="'.route('forms.create').'"', false);
    }

    public function test_custom_role_accounts_also_receive_the_client_dashboard(): void
    {
        $role = Role::query()->create(['code' => 'participant', 'name' => 'Participant']);
        $user = User::factory()->for($role)->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewIs('clients.dashboard')->assertSee('Participant')->assertSee($user->email)
            ->assertSee('No contract assigned yet.')->assertDontSee('Workspace overview');
    }

    public function test_client_dashboard_paginates_all_assigned_contracts(): void
    {
        $user = User::factory()->create();
        Contract::factory()->count(11)->hasAttached($user, [], 'clients')->create();
        Contract::factory()->create(['name' => 'Unrelated contract outside pagination']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('contracts', fn ($contracts) => $contracts->total() === 11 && $contracts->count() === 10)
            ->assertSee('Next')->assertDontSee('Unrelated contract outside pagination');
        $this->get(route('dashboard', ['page' => 2]))->assertOk()
            ->assertViewHas('contracts', fn ($contracts) => $contracts->total() === 11 && $contracts->count() === 1)
            ->assertSee('Previous')->assertDontSee('Unrelated contract outside pagination');
    }

    public function test_inactive_users_cannot_open_their_account_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_comparison_charts_show_empty_states_without_dividing_by_zero(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->for(Role::where('code', Role::ADMIN)->sole())->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertViewHas('stats', ['active_clients' => 0, 'inactive_clients' => 0, 'active_contracts' => 0, 'inactive_contracts' => 0, 'published_forms' => 0])
            ->assertViewHas('formStatusCounts', ['published' => 0, 'draft' => 0, 'archived' => 0])
            ->assertSee('No clients yet.')->assertSee('No contracts yet.')->assertSee('No forms yet.');
    }

    public function test_status_charts_render_when_all_records_share_one_status(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->for(Role::where('code', Role::ADMIN)->sole())->create();
        $client = User::factory()->for(Role::where('code', Role::AGENCY)->sole())->create();
        $contract = Contract::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertViewHas('stats', ['active_clients' => 1, 'inactive_clients' => 0, 'active_contracts' => 1, 'inactive_contracts' => 0, 'published_forms' => 0])
            ->assertSee('Client status')->assertSee('Contract status')->assertSee('100.0%')->assertSee('0.0%')->assertDontSee('No clients yet.');

        $client->is_active = false;
        $client->save();
        $contract->update(['is_active' => false]);
        $this->get(route('dashboard'))->assertOk()
            ->assertViewHas('stats', ['active_clients' => 0, 'inactive_clients' => 1, 'active_contracts' => 0, 'inactive_contracts' => 1, 'published_forms' => 0])
            ->assertSee('Client status')->assertSee('Contract status')->assertSee('100.0%')->assertSee('0.0%')->assertDontSee('No clients yet.');
    }

    public function test_the_homepage_client_login_links_to_the_login_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Client Login');
    }
}

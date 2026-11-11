<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use App\Services\Forms\FormService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $this->seed(RoleSeeder::class);

        return User::factory()->for(Role::query()->where('code', $role)->firstOrFail())->create();
    }

    public function test_only_admins_can_manage_contracts(): void
    {
        $this->get(route('contracts.index'))->assertRedirect(route('login'));
        $client = $this->account(Role::AGENCY);
        $admin = $this->account(Role::ADMIN);
        $unassigned = User::factory()->create();
        $contract = Contract::factory()->create();
        $this->assertTrue(Gate::forUser($admin)->allows('manage-contracts'));
        $this->assertFalse(Gate::forUser($client)->allows('manage-contracts'));
        $this->assertFalse(Gate::forUser($unassigned)->allows('manage-contracts'));

        $this->actingAs($client)->get(route('contracts.index'))->assertForbidden();
        $this->get(route('contracts.create'))->assertForbidden();
        $this->post(route('contracts.store'), ['name' => 'Forbidden'])->assertForbidden();
        $this->get(route('contracts.edit', $contract))->assertForbidden();
        $this->put(route('contracts.update', $contract), ['name' => 'Forbidden'])->assertForbidden();
        $this->delete(route('contracts.destroy', $contract))->assertForbidden();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'name' => $contract->name]);
        $this->get(route('forms.index'))->assertDontSee('href="'.route('contracts.index').'"', false);
    }

    public function test_admin_can_create_a_contract_attach_one_form_and_assign_a_client(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $form = app(FormService::class)->create($admin, 'Inspection checklist');
        $this->actingAs($admin)->get(route('contracts.create'))->assertOk()->assertSee('Inspection checklist');

        $this->post(route('contracts.store'), [
            'name' => 'Annual inspections', 'description' => 'Site inspection agreement.',
            'client_id' => $client->id, 'form_id' => $form->id, 'id' => 99999,
        ])->assertRedirect(route('contracts.index'));

        $contract = Contract::query()->sole();
        $this->assertDatabaseHas('contracts', ['name' => 'Annual inspections', 'description' => 'Site inspection agreement.', 'client_id' => $client->id, 'form_id' => $form->id]);
        $this->assertTrue($contract->form->is($form));
        $this->assertTrue($form->contract->is($contract));
        $this->assertTrue($client->contracts()->sole()->is($contract));
        $this->get(route('contracts.index'))->assertOk()->assertSee('Annual inspections')->assertSee($client->name)->assertSee('Inspection checklist')->assertSee('id="fm-confirm"', false);
        $this->get(route('forms.index'))->assertOk()->assertSee('Annual inspections');
        $this->actingAs($client)->get(route('forms.edit', $form))->assertForbidden();
    }

    public function test_contract_can_be_created_before_attaching_a_form_or_assigning_a_client(): void
    {
        $admin = $this->account(Role::ADMIN);

        $this->actingAs($admin)->post(route('contracts.store'), ['name' => 'Pending agreement'])->assertRedirect(route('contracts.index'));

        $this->assertDatabaseHas('contracts', ['name' => 'Pending agreement', 'client_id' => null, 'form_id' => null]);
        $this->get(route('contracts.index'))->assertOk()->assertSee('Unassigned')->assertSee('No form attached');
    }

    public function test_admin_can_replace_the_form_and_client_then_clear_both_assignments(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $first = app(FormService::class)->create($admin, 'Original form');
        $second = app(FormService::class)->create($admin, 'Replacement form');
        $contract = Contract::factory()->for($first, 'form')->create();

        $this->actingAs($admin)->get(route('contracts.edit', $contract))->assertOk()->assertSee('Original form')->assertSee('Replacement form');
        $this->put(route('contracts.update', $contract), ['name' => 'Updated agreement', 'client_id' => $client->id, 'form_id' => $second->id])->assertRedirect(route('contracts.index'));

        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'name' => 'Updated agreement', 'client_id' => $client->id, 'form_id' => $second->id]);
        $this->assertNull($first->fresh()->contract);

        $this->put(route('contracts.update', $contract), ['name' => 'Updated agreement', 'client_id' => '', 'form_id' => ''])->assertRedirect(route('contracts.index'));
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'client_id' => null, 'form_id' => null]);
        $this->assertModelExists($second);
    }

    public function test_form_cannot_be_attached_to_two_contracts_or_stolen_on_update(): void
    {
        $admin = $this->account(Role::ADMIN);
        $form = app(FormService::class)->create($admin, 'Reserved checklist');
        $existing = Contract::factory()->for($form, 'form')->create();
        $other = Contract::factory()->create();
        $data = ['name' => 'Conflicting agreement', 'form_id' => $form->id];

        $this->actingAs($admin)->post(route('contracts.store'), $data)->assertSessionHasErrors(['form_id' => 'This form is already attached to another contract.']);
        $this->put(route('contracts.update', $other), $data)->assertSessionHasErrors('form_id');

        $this->assertDatabaseCount('contracts', 2);
        $this->assertDatabaseHas('contracts', ['id' => $existing->id, 'form_id' => $form->id]);
        $this->assertDatabaseHas('contracts', ['id' => $other->id, 'form_id' => null, 'name' => $other->name]);
        $this->get(route('contracts.edit', $other))->assertOk()->assertDontSee('Reserved checklist');
        $this->put(route('contracts.update', $existing), ['name' => 'Renamed agreement', 'form_id' => $form->id])->assertRedirect(route('contracts.index'));
    }

    public function test_assignments_reject_admins_deleted_clients_missing_and_deleted_forms(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $client->delete();
        $form = app(FormService::class)->create($admin, 'Removed form');
        $form->delete();

        $this->actingAs($admin)->post(route('contracts.store'), ['name' => '', 'client_id' => $admin->id, 'form_id' => 99999])->assertSessionHasErrors(['name', 'client_id', 'form_id']);
        $this->post(route('contracts.store'), ['name' => 'Invalid assignment', 'client_id' => $client->id, 'form_id' => $form->id])->assertSessionHasErrors(['client_id', 'form_id']);
        $this->post(route('contracts.store'), ['name' => str_repeat('x', 256), 'description' => str_repeat('x', 10001)])->assertSessionHasErrors(['name', 'description']);

        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_removing_a_contract_retains_its_form_and_releases_it_for_another_contract(): void
    {
        $admin = $this->account(Role::ADMIN);
        $form = app(FormService::class)->create($admin, 'Retained checklist');
        $contract = Contract::factory()->for($form, 'form')->create();

        $this->actingAs($admin)->delete(route('contracts.destroy', $contract))->assertRedirect(route('contracts.index'));

        $this->assertModelMissing($contract);
        $this->assertNotSoftDeleted($form);
        $this->get(route('contracts.create'))->assertOk()->assertSee('Retained checklist');
        $this->get(route('contracts.edit', $contract))->assertNotFound();
    }

    public function test_contract_table_filters_clients_and_escapes_contract_names(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $contract = Contract::factory()->for($client, 'client')->create(['name' => '<script>alert(1)</script>']);
        $other = Contract::factory()->create(['name' => 'Other agreement']);

        $this->actingAs($admin)->get(route('contracts.index', ['client_id' => $client->id, 'q' => 'alert']))->assertOk()
            ->assertSee($contract->name)->assertDontSee($contract->name, false)->assertDontSee($other->name);
        $this->get(route('contracts.index', ['q' => 'Missing agreement']))->assertOk()->assertSee('No contracts found.');
    }

    public function test_removing_a_client_or_form_preserves_contract_history(): void
    {
        $admin = $this->account(Role::ADMIN);
        $client = $this->account(Role::AGENCY);
        $form = app(FormService::class)->create($admin, 'Historical checklist');
        $contract = Contract::factory()->for($client, 'client')->for($form, 'form')->create();
        $client->delete();
        $form->delete();

        $this->actingAs($admin)->get(route('contracts.index'))->assertOk()->assertSee($client->name)->assertSee('Historical checklist')->assertSee('removed client')->assertSee('removed form');
        $this->get(route('contracts.edit', $contract))->assertOk()->assertSee('The previously assigned client was removed.')->assertSee('The previously attached form was removed.');
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'client_id' => $client->id, 'form_id' => $form->id]);
    }
}

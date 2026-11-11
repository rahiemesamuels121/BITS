<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractClientAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);

        return User::factory()->for(Role::query()->where('code', Role::ADMIN)->sole())->create();
    }

    private function client(array $attributes = []): User
    {
        return User::factory()->for(Role::query()->where('code', Role::AGENCY)->sole())->create($attributes);
    }

    public function test_new_contract_assignments_hide_clients_on_active_and_inactive_contracts(): void
    {
        $admin = $this->admin();
        $available = $this->client();
        $allocated = $this->client();
        $inactiveAllocation = $this->client();
        Contract::factory()->hasAttached($allocated, [], 'clients')->create();
        Contract::factory()->hasAttached($inactiveAllocation, [], 'clients')->create([
            'is_active' => false, 'is_indefinite' => false,
            'starts_at' => '2025-01-01', 'ends_at' => '2025-01-31',
        ]);

        $this->actingAs($admin)->get(route('contracts.create'))->assertOk()
            ->assertSee($available->email)->assertDontSee($allocated->email)->assertDontSee($inactiveAllocation->email);
    }

    public function test_edit_retains_current_clients_and_available_clients_but_hides_other_assignments(): void
    {
        $admin = $this->admin();
        $current = $this->client();
        $available = $this->client();
        $allocated = $this->client();
        $contract = Contract::factory()->hasAttached($current, [], 'clients')->create();
        Contract::factory()->hasAttached($allocated, [], 'clients')->create();

        $this->actingAs($admin)->get(route('contracts.edit', $contract))->assertOk()
            ->assertSee($current->email)->assertSee($available->email)->assertDontSee($allocated->email)
            ->assertSee('name="client_ids[]" value="'.$current->id.'" checked', false);
        $this->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [$current->id, $available->id],
        ])->assertRedirect(route('contracts.index'));
        $this->assertEqualsCanonicalizing([$current->id, $available->id], $contract->fresh()->clients->modelKeys());
    }

    public function test_a_stale_assignment_form_cannot_assign_a_client_already_taken_elsewhere(): void
    {
        $admin = $this->admin();
        $taken = $this->client();
        $available = $this->client();
        $this->actingAs($admin)->get(route('contracts.create'))->assertOk()->assertSee($taken->email);
        $existing = Contract::factory()->hasAttached($taken, [], 'clients')->create();

        $this->post(route('contracts.store'), [
            'name' => 'Conflicting contract', 'client_ids' => [$taken->id, $available->id],
        ])->assertSessionHasErrors('client_ids.0');
        $this->assertDatabaseCount('contracts', 1);
        $this->assertDatabaseCount('contract_user', 1);
        $this->assertTrue($taken->contracts()->sole()->is($existing));
        $this->assertCount(0, $available->contracts);
    }

    public function test_legacy_shared_clients_stay_visible_and_can_be_retained_on_the_current_contract(): void
    {
        $admin = $this->admin();
        $shared = $this->client();
        $otherOnly = $this->client();
        $contract = Contract::factory()->hasAttached($shared, [], 'clients')->create();
        $otherContract = Contract::factory()->hasAttached([$shared, $otherOnly], [], 'clients')->create();

        $this->actingAs($admin)->get(route('contracts.edit', $contract))->assertOk()
            ->assertSee($shared->email)->assertDontSee($otherOnly->email)
            ->assertSee('Assigned to this contract')
            ->assertSee('name="client_ids[]" value="'.$shared->id.'" checked', false);
        $this->put(route('contracts.update', $contract), [
            'name' => 'Updated contract details', 'client_ids' => [$shared->id],
        ])->assertRedirect(route('contracts.index'));

        $this->assertSame('Updated contract details', $contract->fresh()->name);
        $this->assertEquals([$shared->id], $contract->fresh()->clients->modelKeys());
        $this->assertEqualsCanonicalizing([$shared->id, $otherOnly->id], $otherContract->fresh()->clients->modelKeys());

        $this->put(route('contracts.update', $contract), [
            'name' => 'Conflicting update', 'client_ids' => [$shared->id, $otherOnly->id],
        ])->assertSessionHasErrors('client_ids.1');
        $this->assertSame('Updated contract details', $contract->fresh()->name);
        $this->assertEquals([$shared->id], $contract->fresh()->clients->modelKeys());
    }

    public function test_a_detached_legacy_client_cannot_be_added_back_while_assigned_elsewhere(): void
    {
        $admin = $this->admin();
        $shared = $this->client();
        $contract = Contract::factory()->hasAttached($shared, [], 'clients')->create();
        $otherContract = Contract::factory()->hasAttached($shared, [], 'clients')->create();

        $this->actingAs($admin)->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [],
        ])->assertRedirect(route('contracts.index'));
        $this->get(route('contracts.edit', $contract))->assertOk()->assertDontSee($shared->email);
        $this->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [$shared->id],
        ])->assertSessionHasErrors('client_ids.0');

        $this->assertCount(0, $contract->fresh()->clients);
        $this->assertTrue($shared->contracts()->sole()->is($otherContract));
    }

    public function test_conflicting_updates_preserve_contract_details_and_existing_assignments(): void
    {
        $admin = $this->admin();
        $taken = $this->client();
        $current = $this->client();
        $available = $this->client();
        $existing = Contract::factory()->hasAttached($taken, [], 'clients')->create();
        $contract = Contract::factory()->hasAttached($current, [], 'clients')->create();

        $this->actingAs($admin)->put(route('contracts.update', $contract), [
            'name' => 'Invalid change', 'is_active' => false,
            'client_ids' => [$taken->id, $available->id],
        ])->assertSessionHasErrors('client_ids.0');
        $this->assertSame($contract->name, $contract->fresh()->name);
        $this->assertTrue($contract->fresh()->is_active);
        $this->assertEquals([$current->id], $contract->fresh()->clients->modelKeys());
        $this->assertTrue($taken->contracts()->sole()->is($existing));
        $this->assertDatabaseMissing('contract_user', ['user_id' => $available->id]);
    }

    public function test_detaching_or_deleting_a_contract_releases_clients_for_reassignment(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $contract = Contract::factory()->hasAttached($client, [], 'clients')->create();
        $this->actingAs($admin)->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [],
        ])->assertRedirect(route('contracts.index'));
        $this->get(route('contracts.create'))->assertOk()->assertSee($client->email);

        $this->post(route('contracts.store'), ['name' => 'New contract', 'client_ids' => [$client->id]])
            ->assertRedirect(route('contracts.index'));
        $newContract = $client->contracts()->sole();
        $this->assertFalse($newContract->is($contract));
        $this->delete(route('contracts.destroy', $newContract))->assertRedirect(route('contracts.index'));
        $this->assertDatabaseMissing('contract_user', ['user_id' => $client->id]);
        $this->get(route('contracts.create'))->assertOk()->assertSee($client->email);
    }

    public function test_existing_assignments_still_appear_in_client_management_and_contract_filters(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $contract = Contract::factory()->hasAttached($client, [], 'clients')->create();

        $this->actingAs($admin)->get(route('clients.index'))->assertOk()->assertSee($client->email);
        $this->get(route('contracts.index', ['client_id' => $client->id]))->assertOk()
            ->assertViewHas('clients', fn ($clients) => $clients->contains('id', $client->id))
            ->assertViewHas('contracts', fn ($contracts) => $contracts->contains('id', $contract->id));
    }
}

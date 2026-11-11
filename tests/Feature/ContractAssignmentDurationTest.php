<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractAssignmentDurationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);

        return User::factory()->for(Role::query()->where('code', Role::ADMIN)->sole())->create();
    }

    private function client(): User
    {
        return User::factory()->for(Role::query()->where('code', Role::AGENCY)->sole())->create();
    }

    private function customDates(string $start = '2027-02-01', string $end = '2027-04-30'): array
    {
        return ['duration' => 'custom', 'starts_at' => $start, 'ends_at' => $end];
    }

    public function test_selected_clients_can_have_different_assignment_durations(): void
    {
        $admin = $this->admin();
        $inherited = $this->client();
        $custom = $this->client();

        $this->actingAs($admin)->post(route('contracts.store'), [
            'name' => 'Work and travel', 'is_indefinite' => false,
            'starts_at' => '2027-01-01', 'ends_at' => '2027-12-31',
            'client_ids' => [$inherited->id, $custom->id],
            'client_assignments' => [
                $inherited->id => ['duration' => 'contract'],
                $custom->id => $this->customDates(),
            ],
        ])->assertRedirect(route('contracts.index'));

        $contract = Contract::query()->sole();
        $clients = $contract->clients->keyBy('id');
        $this->assertInstanceOf(ContractAssignment::class, $clients[$inherited->id]->pivot);
        $this->assertTrue($clients[$inherited->id]->pivot->uses_contract_duration);
        $this->assertNull($clients[$inherited->id]->pivot->starts_at);
        $this->assertSame('2027-01-01', $clients[$inherited->id]->pivot->startsAtFor($contract)->toDateString());
        $this->assertSame('2027-12-31', $clients[$inherited->id]->pivot->endsAtFor($contract)->toDateString());
        $this->assertFalse($clients[$custom->id]->pivot->uses_contract_duration);
        $this->assertSame('2027-02-01', $clients[$custom->id]->pivot->starts_at->toDateString());
        $this->assertSame('2027-04-30', $clients[$custom->id]->pivot->ends_at->toDateString());
        $this->assertSame('2027-02-01', $clients[$custom->id]->pivot->startsAtFor($contract)->toDateString());

        $this->get(route('contracts.edit', $contract))->assertOk()
            ->assertSee('client_assignments['.$custom->id.'][duration]', false)
            ->assertSee('2027-02-01')->assertSee('2027-04-30');
    }

    public function test_existing_custom_duration_is_preserved_when_omitted_and_cleared_when_contract_duration_is_selected(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $contract = Contract::factory()->create();
        $this->actingAs($admin)->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [$client->id],
            'client_assignments' => [$client->id => $this->customDates()],
        ])->assertRedirect(route('contracts.index'));

        $this->put(route('contracts.update', $contract), [
            'name' => 'Other details changed', 'client_ids' => [$client->id],
        ])->assertRedirect(route('contracts.index'));
        $pivot = $contract->fresh()->clients->sole()->pivot;
        $this->assertFalse($pivot->uses_contract_duration);
        $this->assertSame('2027-02-01', $pivot->starts_at->toDateString());
        $this->assertSame('2027-04-30', $pivot->ends_at->toDateString());

        $this->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [$client->id],
            'client_assignments' => [$client->id => ['duration' => 'contract', 'starts_at' => 'stale', 'ends_at' => 'stale']],
        ])->assertRedirect(route('contracts.index'));
        $pivot = $contract->fresh()->clients->sole()->pivot;
        $this->assertTrue($pivot->uses_contract_duration);
        $this->assertNull($pivot->starts_at);
        $this->assertNull($pivot->ends_at);
        $this->assertTrue($pivot->isIndefiniteFor($contract->fresh()));
    }

    public function test_inherited_assignment_dates_follow_contract_changes_and_indefinite_contracts(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $contract = Contract::factory()->hasAttached($client, [], 'clients')->create([
            'is_indefinite' => false, 'starts_at' => '2027-01-01', 'ends_at' => '2027-12-31',
        ]);
        $this->actingAs($admin)->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [$client->id], 'is_indefinite' => false,
            'starts_at' => '2028-01-01', 'ends_at' => '2028-12-31',
        ])->assertRedirect(route('contracts.index'));
        $assignment = $client->fresh()->contracts->sole();
        $this->assertTrue($assignment->pivot->uses_contract_duration);
        $this->assertSame('2028-01-01', $assignment->pivot->startsAtFor($assignment)->toDateString());
        $this->assertSame('2028-12-31', $assignment->pivot->endsAtFor($assignment)->toDateString());
        $this->assertFalse($assignment->pivot->isIndefiniteFor($assignment));

        $this->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [$client->id], 'is_indefinite' => true,
        ])->assertRedirect(route('contracts.index'));
        $assignment = $client->fresh()->contracts->sole();
        $this->assertTrue($assignment->pivot->isIndefiniteFor($assignment));
        $this->assertNull($assignment->pivot->startsAtFor($assignment));
        $this->assertNull($assignment->pivot->endsAtFor($assignment));
    }

    public function test_invalid_assignment_ranges_do_not_partially_change_the_contract_or_its_clients(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $contract = Contract::factory()->hasAttached($client, [], 'clients')->create();
        $invalid = [
            [['duration' => 'custom'], 'starts_at'],
            [['duration' => 'custom', 'starts_at' => '2027-02-01'], 'ends_at'],
            [$this->customDates('2027-04-01', '2027-03-01'), 'ends_at'],
            [$this->customDates('2027-02-30', '2027-04-30'), 'starts_at'],
            [['duration' => 'unexpected'], 'duration'],
        ];
        foreach ($invalid as [$settings, $errorField]) {
            $this->actingAs($admin)->put(route('contracts.update', $contract), [
                'name' => 'Invalid partial change', 'is_active' => false,
                'client_ids' => [$client->id], 'client_assignments' => [$client->id => $settings],
            ])->assertSessionHasErrors('client_assignments.'.$client->id.'.'.$errorField);
            $this->assertSame($contract->name, $contract->fresh()->name);
            $this->assertTrue($contract->fresh()->is_active);
            $this->assertTrue($contract->fresh()->clients->sole()->pivot->uses_contract_duration);
        }
    }

    public function test_metadata_for_unselected_clients_is_ignored_and_deselecting_detaches_the_assignment(): void
    {
        $admin = $this->admin();
        $selected = $this->client();
        $unselected = $this->client();
        $contract = Contract::factory()->hasAttached($unselected, [], 'clients')->create();
        $this->actingAs($admin)->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => [$selected->id],
            'client_assignments' => [$unselected->id => ['duration' => 'custom', 'starts_at' => 'invalid']],
        ])->assertRedirect(route('contracts.index'));

        $this->assertEquals([$selected->id], $contract->fresh()->clients->modelKeys());
        $this->assertTrue($contract->fresh()->clients->sole()->pivot->uses_contract_duration);
        $this->assertDatabaseMissing('contract_user', ['contract_id' => $contract->id, 'user_id' => $unselected->id]);

        $this->put(route('contracts.update', $contract), [
            'name' => $contract->name, 'client_ids' => null,
            'client_assignments' => [$selected->id => ['duration' => 'custom', 'starts_at' => 'invalid']],
        ])->assertRedirect(route('contracts.index'));
        $this->assertCount(0, $contract->fresh()->clients);
    }

    public function test_clients_see_their_own_assignment_dates_without_other_clients_dates(): void
    {
        $admin = $this->admin();
        $first = $this->client();
        $second = $this->client();
        $this->actingAs($admin)->post(route('contracts.store'), [
            'name' => 'Shared contract terms', 'is_indefinite' => false,
            'starts_at' => '2027-01-01', 'ends_at' => '2027-12-31',
            'client_ids' => [$first->id, $second->id], 'client_assignments' => [
                $first->id => $this->customDates('2027-02-01', '2027-03-31'),
                $second->id => $this->customDates('2027-08-01', '2027-09-30'),
            ],
        ])->assertRedirect(route('contracts.index'));

        $this->actingAs($first)->get(route('dashboard'))->assertOk()
            ->assertSee('Your assignment')->assertSee('Custom dates')
            ->assertSee('Feb 1, 2027')->assertSee('Mar 31, 2027')
            ->assertSee('Jan 1, 2027')->assertSee('Dec 31, 2027')
            ->assertDontSee('Aug 1, 2027')->assertDontSee('Sep 30, 2027')->assertDontSee($second->email);
        $this->actingAs($second)->get(route('dashboard'))->assertOk()
            ->assertSee('Aug 1, 2027')->assertSee('Sep 30, 2027')
            ->assertDontSee('Feb 1, 2027')->assertDontSee('Mar 31, 2027')->assertDontSee($first->email);
    }
}

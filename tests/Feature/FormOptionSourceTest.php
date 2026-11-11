<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use App\Services\Forms\FormOptionSourceService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormOptionSourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);

        return User::factory()->for(Role::where('code', Role::ADMIN)->sole())->create();
    }

    public function test_sources_are_available_only_to_admins(): void
    {
        $this->postJson(route('forms.option-sources'), [])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson(route('forms.option-sources'), [])->assertForbidden();
    }

    public function test_arbitrary_tables_columns_and_filter_operators_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $this->postJson(route('forms.option-sources'), ['source' => 'sessions', 'column' => 'payload'])->assertUnprocessable();
        $this->postJson(route('forms.option-sources'), ['source' => 'users', 'column' => 'password'])->assertUnprocessable();
        $this->postJson(route('forms.option-sources'), ['source' => 'users', 'column' => 'name', 'filters' => [
            ['column' => 'password', 'operator' => 'equals', 'value' => 'test'],
        ]])->assertUnprocessable();
        $this->postJson(route('forms.option-sources'), ['source' => 'users', 'column' => 'name', 'filters' => [
            ['column' => 'name', 'operator' => 'raw', 'value' => 'test'],
        ]])->assertUnprocessable();
    }

    public function test_filters_combine_and_deleted_accounts_are_excluded(): void
    {
        $admin = $this->admin();
        $agency = Role::where('code', Role::AGENCY)->sole();
        $first = User::factory()->for($agency)->create(['name' => 'Travel Agency']);
        $second = User::factory()->for($agency)->create(['name' => 'Travel Agency']);
        User::factory()->for($agency)->create(['name' => 'Travel Inactive', 'is_active' => false]);
        User::factory()->for($agency)->create(['name' => 'Travel Deleted'])->delete();
        $this->actingAs($admin)->postJson(route('forms.option-sources'), [
            'source' => 'users', 'column' => 'name', 'filters' => [
                ['column' => 'role_id', 'operator' => 'equals', 'value' => (string) $agency->id],
                ['column' => 'is_active', 'operator' => 'equals', 'value' => '1'],
                ['column' => 'name', 'operator' => 'contains', 'value' => 'Travel'],
            ],
        ])->assertOk()->assertExactJson(['options' => [(string) $first->id, (string) $second->id], 'option_labels' => [(string) $first->id => 'Travel Agency', (string) $second->id => 'Travel Agency']]);
    }

    public function test_contains_treats_wildcards_as_literal_text_and_no_matches_returns_empty_options(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => '100% Travel']);
        $this->actingAs($admin)->postJson(route('forms.option-sources'), [
            'source' => 'users', 'column' => 'name', 'filters' => [['column' => 'name', 'operator' => 'contains', 'value' => '%']],
        ])->assertOk()->assertExactJson(['options' => [(string) $client->id], 'option_labels' => [(string) $client->id => '100% Travel']]);
        $this->postJson(route('forms.option-sources'), [
            'source' => 'roles', 'column' => 'name', 'filters' => [['column' => 'code', 'operator' => 'equals', 'value' => 'missing']],
        ])->assertOk()->assertJsonCount(0, 'options')->assertJsonCount(0, 'option_labels');
    }

    public function test_source_catalog_exposes_named_role_and_status_choices_without_model_classes(): void
    {
        $this->admin();
        $staff = Role::query()->where('code', Role::STAFF)->sole();
        $catalog = app(FormOptionSourceService::class)->catalog();

        $this->assertSame('Staff', ((array) $catalog['users']['filter_columns']['role_id']['choices'])[$staff->id]);
        $this->assertSame('Active', $catalog['users']['filter_columns']['is_active']['choices']['1']);
        $this->assertSame('Inactive', $catalog['users']['filter_columns']['is_active']['choices']['0']);
        $this->assertArrayHasKey('starts_at', $catalog['contracts']['filter_columns']);
        $this->assertArrayHasKey('ends_at', $catalog['contracts']['filter_columns']);
        foreach ($catalog as $source) {
            $this->assertArrayNotHasKey('model', $source);
        }
    }

    public function test_contract_status_and_date_filters_select_only_matching_contracts(): void
    {
        $admin = $this->admin();
        $matching = Contract::factory()->create([
            'name' => 'Current travel contract', 'is_active' => true, 'is_indefinite' => false,
            'starts_at' => '2026-11-11', 'ends_at' => '2026-11-11',
        ]);
        Contract::factory()->create([
            'name' => 'Inactive travel contract', 'is_active' => false, 'is_indefinite' => false,
            'starts_at' => '2026-11-01', 'ends_at' => '2027-05-31',
        ]);
        Contract::factory()->create([
            'name' => 'Expired travel contract', 'is_active' => true, 'is_indefinite' => false,
            'starts_at' => '2025-11-01', 'ends_at' => '2026-05-31',
        ]);
        Contract::factory()->create(['name' => 'Indefinite travel contract', 'is_active' => true]);
        $this->actingAs($admin)->postJson(route('forms.option-sources'), [
            'source' => 'contracts', 'column' => 'name', 'filters' => [
                ['column' => 'is_active', 'operator' => 'equals', 'value' => '1'],
                ['column' => 'starts_at', 'operator' => 'less_or_equal', 'value' => '2026-11-11'],
                ['column' => 'ends_at', 'operator' => 'greater_or_equal', 'value' => '2026-11-11'],
            ],
        ])->assertOk()->assertExactJson([
            'options' => [(string) $matching->id], 'option_labels' => [(string) $matching->id => $matching->name],
        ]);
    }

    public function test_typed_filters_reject_invalid_choices_dates_operators_and_private_columns(): void
    {
        $admin = $this->admin();
        $invalidSources = [
            ['users', ['column' => 'role_id', 'operator' => 'equals', 'value' => '999999']],
            ['users', ['column' => 'is_active', 'operator' => 'equals', 'value' => 'active']],
            ['users', ['column' => 'is_active', 'operator' => 'contains', 'value' => '1']],
            ['contracts', ['column' => 'starts_at', 'operator' => 'greater_or_equal', 'value' => '2026-02-30']],
            ['contracts', ['column' => 'starts_at', 'operator' => 'contains', 'value' => '2026-11-11']],
            ['contracts', ['column' => 'description', 'operator' => 'equals', 'value' => 'Private details']],
            ['forms', ['column' => 'status', 'operator' => 'equals', 'value' => 'unknown-status']],
        ];
        foreach ($invalidSources as [$source, $filter]) {
            $this->actingAs($admin)->postJson(route('forms.option-sources'), [
                'source' => $source, 'column' => $source === 'forms' ? 'title' : 'name', 'filters' => [$filter],
            ])->assertUnprocessable();
        }
    }

    public function test_large_sources_require_narrower_filters(): void
    {
        $admin = $this->admin();
        for ($i = 0; $i < 101; $i++) {
            Role::create(['code' => 'role_'.$i, 'name' => 'Role '.$i]);
        }
        $this->actingAs($admin)->postJson(route('forms.option-sources'), ['source' => 'roles', 'column' => 'name'])
            ->assertUnprocessable()->assertJsonValidationErrors('filters');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Role;
use App\Models\User;
use App\Services\Forms\FormSchemaService;
use App\Services\Forms\FormService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientReferenceSettingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Form $form, ?string $reference = 'selected_client', array $field = []): array
    {
        return [
            'title' => $form->title, 'description' => null, 'revision' => $form->revision,
            'settings' => array_replace(app(FormSchemaService::class)->defaults(), ['client_reference_field' => $reference]),
            'fields' => [array_replace([
                'field_key' => 'selected_client', 'label' => 'Client of choice', 'type' => 'select',
                'required' => false, 'options' => ['12'], 'option_labels' => ['12' => 'JOYS'],
                'validation_rules' => [], 'conditional_rules' => null, 'default_value' => null,
            ], $field)],
        ];
    }

    public function test_reference_setting_uses_stable_key_and_is_snapshotted_with_anonymous_response_settings(): void
    {
        $owner = User::factory()->create();
        $form = app(FormService::class)->create($owner, 'Work application');
        $this->actingAs($owner)->putJson(route('forms.update', $form), $this->payload($form))->assertOk();
        $this->assertSame('selected_client', $form->fresh()->draft_settings['client_reference_field']);
        $this->postJson(route('forms.publish', $form), ['revision' => $form->fresh()->revision])->assertOk();
        $version = $form->fresh()->publishedVersion;
        $this->assertSame('selected_client', $version->settings_json['client_reference_field']);
        $this->assertTrue($version->settings_json['allow_anonymous']);
        $this->assertFalse($version->settings_json['require_authentication']);
        $this->assertFalse($version->schema_json['fields'][0]['required']);

        $this->putJson(route('forms.update', $form), $this->payload($form->fresh(), 'selected_client', ['label' => 'Choose who you want to work with']))->assertOk();
        $this->assertSame('selected_client', $form->fresh()->draft_settings['client_reference_field']);
        $this->assertSame('Client of choice', $version->fresh()->schema_json['fields'][0]['label']);
    }

    public function test_reference_can_be_disabled_without_changing_historical_versions(): void
    {
        $owner = User::factory()->create();
        $form = app(FormService::class)->create($owner, 'Optional reference');
        $this->actingAs($owner)->putJson(route('forms.update', $form), $this->payload($form))->assertOk();
        $this->postJson(route('forms.publish', $form), ['revision' => $form->fresh()->revision])->assertOk();
        $first = $form->fresh()->publishedVersion;
        $this->putJson(route('forms.update', $form), $this->payload($form->fresh(), null))->assertOk();
        $this->postJson(route('forms.publish', $form), ['revision' => $form->fresh()->revision])->assertOk();
        $this->assertNull($form->fresh()->publishedVersion->settings_json['client_reference_field']);
        $this->assertSame('selected_client', $first->fresh()->settings_json['client_reference_field']);
    }

    public function test_missing_wrong_type_and_malformed_reference_questions_are_rejected(): void
    {
        $owner = User::factory()->create();
        $form = app(FormService::class)->create($owner, 'Invalid reference');
        $this->actingAs($owner);
        foreach ([['missing_key', []], ['selected_client', ['type' => 'text', 'options' => []]], ['selected_client.path', []]] as [$key, $field]) {
            $this->putJson(route('forms.update', $form), $this->payload($form, $key, $field))
                ->assertUnprocessable()->assertJsonValidationErrors('settings.client_reference_field');
            $this->assertSame(0, $form->fresh()->revision);
        }
    }

    public function test_another_live_table_cannot_be_used_as_the_client_reference(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->for(Role::query()->where('code', Role::ADMIN)->sole())->create();
        $form = app(FormService::class)->create($admin, 'Wrong table');
        $this->actingAs($admin)->putJson(route('forms.update', $form), $this->payload($form, 'selected_client', [
            'options' => [], 'option_labels' => [],
            'option_source' => ['source' => 'roles', 'column' => 'name', 'filters' => []],
        ]))->assertUnprocessable()->assertJsonValidationErrors('settings.client_reference_field');
    }

    public function test_publish_revalidates_the_reference_against_stored_fields(): void
    {
        $owner = User::factory()->create();
        $form = app(FormService::class)->create($owner, 'Stored reference');
        $this->actingAs($owner)->putJson(route('forms.update', $form), $this->payload($form))->assertOk();
        $form->fields()->delete();
        $this->postJson(route('forms.publish', $form), ['revision' => $form->fresh()->revision])
            ->assertUnprocessable()->assertJsonValidationErrors('settings.client_reference_field');
        $this->assertDatabaseCount('form_versions', 0);
    }
}

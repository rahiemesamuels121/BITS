<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Role;
use App\Models\User;
use App\Services\Forms\FormPublishingService;
use App\Services\Forms\FormSchemaService;
use App\Services\Forms\FormService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientDashboardResponsesTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = Role::AGENCY, array $attributes = []): User
    {
        $this->seed(RoleSeeder::class);

        return User::factory()->for(Role::query()->where('code', $role)->sole())->create($attributes);
    }

    private function field(string $key, string $type = 'text', array $attributes = []): array
    {
        return array_replace([
            'field_key' => $key, 'label' => ucfirst(str_replace('_', ' ', $key)), 'type' => $type,
            'required' => false, 'placeholder' => null, 'help_text' => null, 'options' => [],
            'validation_rules' => [], 'conditional_rules' => null, 'default_value' => null,
        ], $attributes);
    }

    private function publishVersion(Form $form, array $clients, string $key = 'preferred_client', bool $linked = true, array $extraFields = []): Form
    {
        $fields = [
            $this->field('full_name'),
            $this->field($key, 'select', [
                'label' => 'Which client would you like to work with?',
                'options' => array_map(fn (User $client) => (string) $client->id, $clients),
                'option_labels' => collect($clients)->mapWithKeys(fn (User $client) => [(string) $client->id => $client->name])->all(),
            ]),
            $this->field('unrelated_number', 'number'),
            ...$extraFields,
        ];
        $form = app(FormService::class)->update($form->fresh(), [
            'title' => $form->title, 'description' => $form->description, 'revision' => $form->fresh()->revision,
            'fields' => $fields,
            'settings' => array_replace(app(FormSchemaService::class)->defaults(), [
                'client_reference_field' => $linked ? $key : null,
                'allow_anonymous' => true, 'require_authentication' => false,
            ]),
        ]);
        app(FormPublishingService::class)->publish($form, $form->user, $form->revision);

        return $form->fresh();
    }

    private function form(User $owner, array $clients, string $title = 'Programme application', bool $linked = true, array $extraFields = []): Form
    {
        return $this->publishVersion(app(FormService::class)->create($owner, $title), $clients, linked: $linked, extraFields: $extraFields);
    }

    private function response(Form $form, array $answers): FormSubmission
    {
        return $form->submissions()->create([
            'form_version_id' => $form->current_published_version_id,
            'user_id' => null, 'data' => $answers, 'submitted_at' => now(),
        ]);
    }

    public function test_an_anonymous_respondent_can_optionally_link_a_response_to_the_selected_clients_dashboard(): void
    {
        $owner = $this->account(Role::ADMIN);
        $joys = $this->account(attributes: ['name' => 'JOYS']);
        $other = $this->account();
        $form = $this->form($owner, [$joys, $other]);
        Contract::factory()->for($form, 'form')->hasAttached($joys, [], 'clients')->create();
        Auth::logout();

        $this->post(route('public-forms.submit', $form->slug), [
            'version_id' => $form->current_published_version_id,
            'answers' => ['full_name' => 'John Doe', 'preferred_client' => (string) $joys->id],
        ])->assertRedirect(route('public-forms.show', $form->slug));
        $linked = $form->submissions()->sole();
        $this->assertNull($linked->user_id);
        $this->assertSame((string) $joys->id, $linked->data['preferred_client']);
        $this->assertGuest();
        $this->get(route('clients.responses.show', $linked))->assertRedirect(route('login'));

        $this->post(route('public-forms.submit', $form->slug), [
            'version_id' => $form->current_published_version_id,
            'answers' => ['full_name' => 'Unlinked respondent', 'preferred_client' => ''],
        ])->assertRedirect(route('public-forms.show', $form->slug));
        $this->assertDatabaseCount('form_submissions', 2);
        $this->assertNull($form->submissions()->latest('id')->first()->user_id);

        $this->actingAs($joys)->get(route('clients.responses.index'))->assertOk()
            ->assertSee('Responses linked to you')->assertSee($form->title)
            ->assertViewHas('responses', fn ($responses) => $responses->total() === 1 && $responses->contains('id', $linked->id))
            ->assertSee(route('clients.responses.show', $linked));
        $this->get(route('clients.responses.show', $linked))->assertOk()->assertSee('John Doe')->assertDontSee('Unlinked respondent');
        $this->actingAs($other)->get(route('clients.responses.index'))->assertOk()
            ->assertViewHas('responses', fn ($responses) => $responses->total() === 0)
            ->assertSee('No responses linked to you yet.');
        $this->get(route('clients.responses.show', $linked))->assertNotFound();
    }

    public function test_only_exact_scalar_ids_at_the_configured_question_key_match(): void
    {
        // Use ID 1 so boolean true cannot accidentally satisfy the client-ID predicate.
        $joys = $this->account(attributes: ['name' => 'JOYS']);
        $this->assertSame(1, $joys->id);
        $owner = $this->account(Role::ADMIN);
        $other = $this->account();
        $form = $this->form($owner, [$joys, $other]);
        Contract::factory()->for($form, 'form')->hasAttached($joys, [], 'clients')->create();
        $string = $this->response($form, ['preferred_client' => (string) $joys->id]);
        $integer = $this->response($form, ['preferred_client' => $joys->id]);
        foreach ([true, false, [(string) $joys->id], ['id' => $joys->id], $joys->id.'suffix', '0'.$joys->id, '', null] as $invalidValue) {
            $this->response($form, ['preferred_client' => $invalidValue, 'unrelated_number' => $joys->id]);
        }
        $this->response($form, ['unrelated_number' => $joys->id]);
        $this->response($form, ['preferred_client' => (string) $other->id, 'unrelated_number' => $joys->id]);

        $this->actingAs($joys)->get(route('clients.responses.index'))->assertOk()
            ->assertViewHas('responses', fn ($responses) => $responses->total() === 2
                && $responses->contains('id', $string->id) && $responses->contains('id', $integer->id));
    }

    public function test_matching_answers_from_forms_outside_assigned_contracts_are_not_visible(): void
    {
        $owner = $this->account(Role::ADMIN);
        $joys = $this->account();
        $other = $this->account();
        $assigned = $this->form($owner, [$joys], 'Authorized application');
        Contract::factory()->for($assigned, 'form')->hasAttached($joys, [], 'clients')->create(['is_active' => false]);
        $visible = $this->response($assigned, ['preferred_client' => (string) $joys->id, 'full_name' => 'Authorized respondent']);
        $unassigned = $this->form($owner, [$joys], 'Unassigned private form');
        $foreign = $this->form($owner, [$joys], 'Other clients private form');
        Contract::factory()->for($foreign, 'form')->hasAttached($other, [], 'clients')->create();
        $deleted = $this->form($owner, [$joys], 'Removed form');
        Contract::factory()->for($deleted, 'form')->hasAttached($joys, [], 'clients')->create();
        $hidden = [];
        foreach ([$unassigned, $foreign, $deleted] as $form) {
            $hidden[] = $this->response($form, ['preferred_client' => (string) $joys->id, 'full_name' => 'Private respondent']);
        }
        $deleted->delete();

        $this->actingAs($joys)->get(route('clients.responses.index'))->assertOk()
            ->assertViewHas('responses', fn ($responses) => $responses->total() === 1 && $responses->contains('id', $visible->id))
            ->assertDontSee('Unassigned private form')->assertDontSee('Other clients private form')->assertDontSee('Removed form');
        foreach ($hidden as $submission) {
            $this->get(route('clients.responses.show', $submission))->assertNotFound();
        }
        $this->get(route('forms.submissions.index', $assigned))->assertForbidden();
        $this->get(route('forms.export', $assigned))->assertForbidden();
        $this->delete(route('forms.submissions.destroy', [$assigned, $visible]))->assertForbidden();
        $this->assertModelExists($visible);
    }

    public function test_reference_keys_are_taken_from_each_immutable_published_version(): void
    {
        $owner = $this->account(Role::ADMIN);
        $joys = $this->account();
        $form = $this->form($owner, [$joys], linked: false);
        Contract::factory()->for($form, 'form')->hasAttached($joys, [], 'clients')->create();
        $unconfigured = $this->response($form, ['preferred_client' => (string) $joys->id]);
        $form = $this->publishVersion($form, [$joys]);
        $firstKey = $this->response($form, ['preferred_client' => (string) $joys->id, 'full_name' => 'First version respondent']);
        $form = $this->publishVersion($form, [$joys], key: 'selected_agency');
        $secondKey = $this->response($form, ['selected_agency' => (string) $joys->id, 'full_name' => 'Renamed question respondent']);
        $wrongKey = $this->response($form, ['preferred_client' => (string) $joys->id]);

        $this->actingAs($joys)->get(route('clients.responses.index'))->assertOk()
            ->assertViewHas('responses', fn ($responses) => $responses->total() === 2
                && $responses->contains('id', $firstKey->id) && $responses->contains('id', $secondKey->id));
        $this->get(route('clients.responses.show', $firstKey))->assertOk()->assertSee('First version respondent');
        $this->get(route('clients.responses.show', $secondKey))->assertOk()->assertSee('Renamed question respondent');
        $this->get(route('clients.responses.show', $unconfigured))->assertNotFound();
        $this->get(route('clients.responses.show', $wrongKey))->assertNotFound();
    }

    public function test_detaching_the_client_or_the_form_revokes_response_access_immediately(): void
    {
        $owner = $this->account(Role::ADMIN);
        $joys = $this->account();
        $form = $this->form($owner, [$joys]);
        $contract = Contract::factory()->for($form, 'form')->hasAttached($joys, [], 'clients')->create();
        $submission = $this->response($form, ['preferred_client' => (string) $joys->id]);
        $this->actingAs($joys)->get(route('clients.responses.show', $submission))->assertOk();
        $contract->clients()->detach($joys->id);
        $this->get(route('clients.responses.index'))->assertOk()->assertViewHas('responses', fn ($responses) => $responses->total() === 0);
        $this->get(route('clients.responses.show', $submission))->assertNotFound();
        $contract->clients()->attach($joys->id);
        $this->get(route('clients.responses.show', $submission))->assertOk();
        $contract->update(['form_id' => null]);
        $this->get(route('clients.responses.index'))->assertOk()->assertViewHas('responses', fn ($responses) => $responses->total() === 0);
        $this->get(route('clients.responses.show', $submission))->assertNotFound();
    }

    public function test_responses_are_paginated_and_filters_are_preserved(): void
    {
        $owner = $this->account(Role::ADMIN);
        $client = $this->account();
        $form = $this->form($owner, [$client]);
        Contract::factory()->for($form, 'form')->hasAttached($client, [], 'clients')->create();
        for ($i = 0; $i < 11; $i++) {
            $this->response($form, ['preferred_client' => (string) $client->id]);
        }
        $completed = $this->response($form, ['preferred_client' => (string) $client->id]);
        $completed->update(['status' => 'completed', 'submitted_at' => '2026-01-01 12:00:00']);
        $this->actingAs($client)->get(route('clients.responses.index', ['form' => $form->id]))->assertOk()
            ->assertViewHas('responses', fn ($items) => $items->total() === 12 && $items->count() === 10)
            ->assertSee('form='.$form->id)->assertSee('page=2');
        $this->get(route('clients.responses.index', ['status' => 'new', 'page' => 2]))->assertOk()
            ->assertViewHas('responses', fn ($items) => $items->count() === 2 && $items->currentPage() === 2);
        $this->get(route('clients.responses.index', ['status' => 'completed', 'form' => $form->id, 'from' => '2026-01-01', 'to' => '2026-01-01']))->assertOk()
            ->assertViewHas('responses', fn ($items) => $items->total() === 1 && $items->first()->is($completed));
        $this->get(route('clients.responses.index', ['from' => '2026-01-02']))->assertOk()
            ->assertViewHas('responses', fn ($items) => $items->total() === 11);
        $this->get(route('clients.responses.index', ['to' => '2026-01-01']))->assertOk()
            ->assertViewHas('responses', fn ($items) => $items->total() === 1);
        $this->get(route('clients.responses.index', ['form' => 999999]))->assertOk()->assertSee('No responses match your filters.');
        $this->get(route('clients.responses.index', ['from' => 'invalid']))->assertSessionHasErrors('from');
    }

    public function test_clients_export_only_their_linked_responses(): void
    {
        $owner = $this->account(Role::ADMIN);
        $client = $this->account();
        $other = $this->account();
        $form = $this->form($owner, [$client, $other]);
        Contract::factory()->for($form, 'form')->hasAttached($client, [], 'clients')->create();
        $submission = $this->response($form, ['preferred_client' => (string) $client->id, 'full_name' => '=Private answer']);
        $second = $this->response($form, ['preferred_client' => (string) $client->id, 'full_name' => 'Second response', 'unrelated_number' => 42]);
        $this->response($form, ['preferred_client' => (string) $other->id, 'full_name' => 'Other private answer']);
        $download = $this->actingAs($client)->get(route('clients.responses.export'));
        $download->assertOk()->assertDownload('my-responses.csv');
        $content = $download->streamedContent();
        $rows = array_map(fn ($line) => str_getcsv($line, ',', '"', ''), explode("\n", trim(substr($content, 3))));
        $this->assertCount(3, $rows);
        $this->assertSame(['Response ID', 'Form', 'Contract', 'Received (UTC)', 'Full name', 'Which client would you like to work with?', 'Unrelated number'], $rows[0]);
        $this->assertSame((string) $submission->id, $rows[1][0]);
        $this->assertSame("'=Private answer", $rows[1][4]);
        $this->assertSame((string) $second->id, $rows[2][0]);
        $this->assertSame('Second response', $rows[2][4]);
        $this->assertSame('42', $rows[2][6]);
        $this->assertStringNotContainsString('Other private answer', $content);
        $this->assertStringNotContainsString('Status', $content);
        $this->actingAs($owner)->get(route('clients.responses.export'))->assertNotFound();
    }

    public function test_client_attachment_downloads_use_the_same_response_scope_and_hide_storage_paths(): void
    {
        Storage::fake('local');
        $owner = $this->account(Role::ADMIN);
        $joys = $this->account();
        $other = $this->account();
        $form = $this->form($owner, [$joys, $other], extraFields: [$this->field('attachment', 'file')]);
        $contract = Contract::factory()->for($form, 'form')->hasAttached($joys, [], 'clients')->create();
        $path = 'forms/'.$form->id.'/application.txt';
        Storage::disk('local')->put($path, 'Private application');
        $submission = $this->response($form, [
            'preferred_client' => (string) $joys->id, 'full_name' => 'John Doe',
            'attachment' => ['kind' => 'file', 'path' => $path, 'name' => 'application.txt', 'mime' => 'text/plain', 'size' => 19],
        ]);
        $this->actingAs($joys)->get(route('clients.responses.show', $submission))->assertOk()
            ->assertSee('application.txt')->assertDontSee($path)->assertSee(route('clients.responses.file', [$submission, 'attachment']));
        $this->get(route('clients.responses.file', [$submission, 'attachment']))->assertOk()->assertDownload('application.txt');
        $this->get(route('clients.responses.file', [$submission, 'full_name']))->assertNotFound();
        $this->actingAs($other)->get(route('clients.responses.file', [$submission, 'attachment']))->assertNotFound();
        $this->actingAs($joys);
        $contract->clients()->detach($joys->id);
        $this->get(route('clients.responses.file', [$submission, 'attachment']))->assertNotFound();
    }
}

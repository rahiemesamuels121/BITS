<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Services\Forms\FormReportService;
use App\Services\Forms\FormSchemaService;
use App\Services\Forms\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function field(string $key = 'full_name', string $type = 'text', array $extra = []): array
    {
        return array_replace(['field_key' => $key, 'label' => ucfirst(str_replace('_', ' ', $key)), 'type' => $type, 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => [], 'validation_rules' => [], 'conditional_rules' => null, 'default_value' => null], $extra);
    }

    private function payload(Form $form, array $fields, array $settings = []): array
    {
        return ['title' => $form->title, 'description' => $form->description, 'revision' => $form->revision, 'fields' => $fields, 'settings' => array_replace(app(FormSchemaService::class)->defaults(), $settings)];
    }

    private function makeForm(User $owner, array $fields = [], array $settings = []): Form
    {
        $form = app(FormService::class)->create($owner, 'Farmer Registration', 'Tell us about your farm.');
        $this->actingAs($owner)->putJson(route('forms.update', $form), $this->payload($form, $fields ?: [$this->field()], $settings))->assertOk();

        return $form->fresh();
    }

    private function publish(Form $form): Form
    {
        $this->actingAs($form->user)->postJson(route('forms.publish', $form), ['revision' => $form->revision])->assertOk();

        return $form->fresh();
    }

    private function submit(Form $form, array $answers)
    {
        return $this->postJson(route('public-forms.submit', $form->slug), ['version_id' => $form->current_published_version_id, 'answers' => $answers]);
    }

    public function test_the_farmer_registration_workflow_stores_typed_answers_and_exports_them(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->get(route('forms.index'))->assertOk()->assertSee('Form Management');
        $this->post(route('forms.store'), ['title' => 'Farmer Registration', 'description' => 'Tell us about your farm.'])->assertRedirect();
        $form = $owner->forms()->sole();
        $fields = [$this->field(), $this->field('age', 'number', ['validation_rules' => ['min' => 0, 'max' => 120]]), $this->field('parish', 'select', ['options' => ['Kingston', 'St. Catherine', 'Clarendon']]), $this->field('crops', 'checkboxes', ['options' => ['Tomatoes', 'Carrots']])];
        $this->putJson(route('forms.update', $form), $this->payload($form, $fields))->assertOk();
        $form->refresh();
        $this->get(route('forms.edit', $form))->assertOk()->assertSee('data-form-builder', false);
        $this->get(route('forms.preview', $form))->assertOk()->assertSee('Preview mode');
        $form = $this->publish($form);
        Auth::logout();
        $this->get(route('public-forms.show', $form->slug))->assertOk()->assertSee('St. Catherine');
        $this->submit($form, ['full_name' => 'John Brown', 'age' => '35', 'parish' => 'St. Catherine', 'crops' => ['Tomatoes', 'Carrots']])->assertRedirect(route('public-forms.show', $form->slug));
        $response = $form->submissions()->sole();
        $this->assertNull($response->user_id);
        $this->assertSame(['full_name' => 'John Brown', 'age' => 35, 'parish' => 'St. Catherine', 'crops' => ['Tomatoes', 'Carrots']], $response->data);
        $this->assertSame($form->current_published_version_id, $response->form_version_id);
        $this->get(route('public-forms.show', $form->slug))->assertOk()->assertSee('Your response has been recorded');
        $this->actingAs($owner)->get(route('forms.submissions.index', $form))->assertOk()->assertSee('John Brown');
        $this->get(route('forms.submissions.show', [$form, $response]))->assertOk()->assertSee('John Brown')->assertSee('Version 1');
        $csv = $this->get(route('forms.export', $form))->assertOk()->streamedContent();
        $this->assertStringContainsString('John Brown', $csv);
        $this->assertStringContainsString('Tomatoes, Carrots', $csv);
        $this->get(route('forms.analytics', $form))->assertOk()->assertSee('Submissions over time');
    }

    public function test_distinct_forms_share_the_same_submission_table(): void
    {
        $owner = User::factory()->create();
        $first = $this->publish($this->makeForm($owner));
        $second = $this->publish($this->makeForm($owner, [$this->field('contact_email', 'email'), $this->field('consent', 'boolean')]));
        $this->submit($first, ['full_name' => 'John Brown'])->assertRedirect();
        $this->submit($second, ['contact_email' => 'john@example.com', 'consent' => '0'])->assertRedirect();
        $this->assertDatabaseCount('form_submissions', 2);
        $this->assertSame(false, $second->submissions()->sole()->data['consent']);
        $this->assertTrue(Schema::hasTable('forms'));
        $this->assertTrue(Schema::hasTable('form_fields'));
        $this->assertTrue(Schema::hasTable('form_versions'));
    }

    public function test_published_forms_use_immutable_snapshots_and_preserve_historical_answers(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner));
        $oldVersion = $form->current_published_version_id;
        $this->submit($form, ['full_name' => 'Historical Name'])->assertRedirect();
        $data = $this->payload($form, [$this->field('full_name', 'text', ['label' => 'Updated name']), $this->field('age', 'number')]);
        $data['title'] = 'Updated Registration';
        $this->putJson(route('forms.update', $form), $data)->assertOk();
        $form->refresh();
        $this->get(route('public-forms.show', $form->slug))->assertSee('Farmer Registration')->assertDontSee('Updated name');
        $form = $this->publish($form);
        $this->assertDatabaseCount('form_versions', 2);
        $this->assertNotSame($oldVersion, $form->current_published_version_id);
        $submission = $form->submissions()->sole();
        $this->assertSame($oldVersion, $submission->form_version_id);
        $this->assertSame('Full name', $submission->version->schema_json['fields'][0]['label']);
        $this->get(route('forms.submissions.index', $form))->assertOk()->assertSee('Historical Name')->assertSee('Updated name');
        $this->postJson(route('public-forms.submit', $form->slug), ['version_id' => $oldVersion, 'answers' => ['full_name' => 'Stale tab']])->assertUnprocessable()->assertJsonValidationErrors('version_id');
    }

    public function test_unchanged_publications_reuse_a_version_and_models_cannot_mutate_snapshots(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner));
        $form = $this->publish($form);
        $this->assertDatabaseCount('form_versions', 1);
        $this->expectException(LogicException::class);
        $form->publishedVersion->update(['schema_json' => ['title' => 'Changed']]);
    }

    public function test_required_numeric_and_option_validation_reject_invalid_answers_and_unknown_keys(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, [$this->field(), $this->field('age', 'number', ['validation_rules' => ['min' => 18, 'max' => 120]]), $this->field('parish', 'radio', ['options' => ['Kingston']])]));
        $this->submit($form, ['age' => 12, 'parish' => 'Unknown'])->assertUnprocessable()->assertJsonValidationErrors('answers.full_name');
        $this->submit($form, ['full_name' => 'Jane', 'age' => 12, 'parish' => 'Kingston'])->assertUnprocessable()->assertJsonValidationErrors('answers.age');
        $this->submit($form, ['full_name' => 'Jane', 'age' => 25, 'parish' => 'Unknown'])->assertUnprocessable()->assertJsonValidationErrors('answers.parish');
        $this->submit($form, ['full_name' => 'Jane', 'age' => 25, 'parish' => 'Kingston', 'role' => 'admin'])->assertUnprocessable()->assertJsonValidationErrors('answers');
        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_hidden_conditional_answers_are_discarded_and_visible_questions_are_required(): void
    {
        $owner = User::factory()->create();
        $fields = [$this->field('employed', 'radio', ['options' => ['Yes', 'No']]), $this->field('employer', 'text', ['conditional_rules' => ['field' => 'employed', 'operator' => 'equals', 'value' => 'Yes']])];
        $form = $this->publish($this->makeForm($owner, $fields));
        $this->submit($form, ['employed' => 'Yes'])->assertUnprocessable()->assertJsonValidationErrors('answers.employer');
        $this->submit($form, ['employed' => 'No', 'employer' => 'Injected hidden value'])->assertRedirect();
        $this->assertSame(['employed' => 'No'], $form->submissions()->sole()->data);
    }

    public function test_checkbox_contains_and_not_equals_conditions_work(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, [$this->field('crops', 'checkboxes', ['options' => ['Tomatoes', 'Carrots']]), $this->field('details', 'text', ['conditional_rules' => ['field' => 'crops', 'operator' => 'contains', 'value' => 'Tomatoes']]), $this->field('other', 'text', ['conditional_rules' => ['field' => 'crops', 'operator' => 'not_equals', 'value' => 'Carrots']])]));
        $this->submit($form, ['crops' => ['Carrots']])->assertRedirect();
        $this->submit($form, ['crops' => ['Tomatoes']])->assertUnprocessable()->assertJsonValidationErrors('answers.details');
        $this->submit($form, ['crops' => ['Tomatoes'], 'details' => 'Ready'])->assertUnprocessable()->assertJsonValidationErrors('answers.other');
    }

    public function test_other_users_cannot_edit_read_export_or_duplicate_private_forms(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner));
        $this->submit($form, ['full_name' => 'Private person'])->assertRedirect();
        $submission = $form->submissions()->sole();
        $this->actingAs(User::factory()->create());
        foreach (['forms.edit', 'forms.preview', 'forms.settings', 'forms.submissions.index', 'forms.analytics', 'forms.export'] as $route) {
            $this->get(route($route, $form))->assertForbidden();
        }
        $this->post(route('forms.duplicate', $form))->assertForbidden();
        $this->get(route('forms.submissions.show', [$form, $submission]))->assertForbidden();
        $this->get(route('forms.index'))->assertDontSee('Farmer Registration');
        Auth::logout();
        $this->get(route('forms.index'))->assertRedirect(route('login'));
    }

    public function test_duplication_copies_fields_and_settings_but_not_versions_or_submissions(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, settings: ['submit_text' => 'Register now']));
        $this->submit($form, ['full_name' => 'First response'])->assertRedirect();
        $this->post(route('forms.duplicate', $form))->assertRedirect();
        $copy = $owner->forms()->whereKeyNot($form->id)->sole();
        $this->assertSame('draft', $copy->status);
        $this->assertNull($copy->current_published_version_id);
        $this->assertNotSame($form->slug, $copy->slug);
        $this->assertSame('Register now', $copy->draft_settings['submit_text']);
        $this->assertSame(1, $copy->fields()->count());
        $this->assertSame(0, $copy->versions()->count());
        $this->assertSame(0, $copy->submissions()->count());
    }

    public function test_unpublishing_archiving_and_deleted_forms_stop_public_access(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner));
        $this->post(route('forms.unpublish', $form))->assertRedirect();
        $this->get(route('public-forms.show', $form->slug))->assertStatus(410);
        $form = $this->publish($form->fresh());
        $this->post(route('forms.archive', $form))->assertRedirect();
        $this->get(route('public-forms.show', $form->slug))->assertStatus(410);
        $this->delete(route('forms.destroy', $form))->assertRedirect(route('forms.index'));
        $this->get(route('public-forms.show', $form->slug))->assertNotFound();
    }

    public function test_submission_limits_are_enforced_and_the_last_success_still_shows_confirmation(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, settings: ['max_submissions' => 1]));
        $this->submit($form, ['full_name' => 'First person'])->assertRedirect();
        $this->get(route('public-forms.show', $form->slug))->assertOk()->assertSee('Response recorded');
        $this->submit($form, ['full_name' => 'Second person'])->assertStatus(410);
        $this->assertDatabaseCount('form_submissions', 1);
    }

    public function test_authentication_and_one_response_per_user_settings_are_enforced(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, settings: ['require_authentication' => true, 'one_per_user' => true]));
        Auth::logout();
        $this->get(route('public-forms.show', $form->slug))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->submit($form, ['full_name' => 'Respondent'])->assertRedirect();
        $this->submit($form, ['full_name' => 'Second response'])->assertUnprocessable()->assertJsonValidationErrors('form');
        $this->assertDatabaseCount('form_submissions', 1);
    }

    public function test_files_are_private_authorized_and_removed_when_a_response_is_deleted(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, [$this->field('proof', 'file', ['validation_rules' => ['allowed_types' => ['pdf'], 'max_size' => 100]])]));
        $this->post(route('public-forms.submit', $form->slug), ['version_id' => $form->current_published_version_id, 'answers' => ['proof' => UploadedFile::fake()->create('report.pdf', 20, 'application/pdf')]])->assertRedirect();
        $submission = $form->submissions()->sole();
        $file = $submission->data['proof'];
        Storage::disk('local')->assertExists($file['path']);
        $this->assertNotSame('report.pdf', basename($file['path']));
        $this->get(route('forms.submissions.file', [$form, $submission, 'proof']))->assertDownload('report.pdf');
        $this->actingAs(User::factory()->create())->get(route('forms.submissions.file', [$form, $submission, 'proof']))->assertForbidden();
        $this->actingAs($owner)->delete(route('forms.submissions.destroy', [$form, $submission]))->assertRedirect();
        Storage::disk('local')->assertMissing($file['path']);
        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_executable_extensions_wrong_mime_types_and_oversize_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, [$this->field('proof', 'file', ['validation_rules' => ['allowed_types' => ['pdf'], 'max_size' => 100]])]));
        foreach ([UploadedFile::fake()->create('script.php', 1, 'application/pdf'), UploadedFile::fake()->create('wrong.pdf', 1, 'text/plain'), UploadedFile::fake()->create('large.pdf', 200, 'application/pdf')] as $upload) {
            $this->from(route('public-forms.show', $form->slug))->post(route('public-forms.submit', $form->slug), ['version_id' => $form->current_published_version_id, 'answers' => ['proof' => $upload]])->assertSessionHasErrors('answers.proof');
        }
        $this->assertDatabaseCount('form_submissions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_empty_or_invalid_schemas_cannot_be_published_and_stale_drafts_cannot_overwrite(): void
    {
        $owner = User::factory()->create();
        $form = app(FormService::class)->create($owner, 'Empty form');
        $this->actingAs($owner)->postJson(route('forms.publish', $form), ['revision' => 0])->assertUnprocessable()->assertJsonValidationErrors('fields');
        $payload = $this->payload($form, [$this->field()]);
        $this->putJson(route('forms.update', $form), $payload)->assertOk();
        $payload['title'] = 'Stale overwrite';
        $this->putJson(route('forms.update', $form), $payload)->assertStatus(409);
        $this->assertSame('Empty form', $form->fresh()->title);
    }

    public function test_duplicate_keys_forward_conditions_and_unsafe_file_types_are_rejected(): void
    {
        $owner = User::factory()->create();
        $form = app(FormService::class)->create($owner, 'Validation');
        $this->actingAs($owner);
        $this->putJson(route('forms.update', $form), $this->payload($form, [$this->field(), $this->field()]))->assertUnprocessable();
        $this->putJson(route('forms.update', $form), $this->payload($form, [$this->field('first', extra: ['conditional_rules' => ['field' => 'later', 'operator' => 'equals', 'value' => 'Yes']]), $this->field('later')]))->assertUnprocessable();
        $this->putJson(route('forms.update', $form), $this->payload($form, [$this->field('upload', 'file', ['validation_rules' => ['allowed_types' => ['php']]])]))->assertUnprocessable();
        $this->assertSame(0, $form->fields()->count());
        $this->putJson(route('forms.update', $form), $this->payload($form, [$this->field(extra: ['conditional_rules' => []])]))->assertOk();
        $this->assertNull($form->fresh()->fields()->sole()->conditional_rules);
    }

    public function test_export_escapes_spreadsheet_formulas_and_filters_are_applied(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner));
        $this->submit($form, ['full_name' => '=HYPERLINK("https://example.com")'])->assertRedirect();
        $this->submit($form, ['full_name' => 'Ordinary response'])->assertRedirect();
        $csv = $this->get(route('forms.export', $form))->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $filtered = $this->get(route('forms.export', ['form' => $form, 'q' => 'Ordinary']))->streamedContent();
        $this->assertStringContainsString('Ordinary response', $filtered);
        $this->assertStringNotContainsString('HYPERLINK', $filtered);
    }

    public function test_labels_are_escaped_and_preview_requests_do_not_create_responses(): void
    {
        $owner = User::factory()->create();
        $form = $this->makeForm($owner, [$this->field(extra: ['label' => '<script>alert(1)</script>'])]);
        $this->get(route('forms.preview', $form))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_deadlines_and_response_settings_apply_only_after_publication(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner));
        $this->putJson(route('forms.update', $form), $this->payload($form, [$this->field()], ['accept_responses' => false]))->assertOk();
        $this->get(route('public-forms.show', $form->slug))->assertOk();
        $form = $this->publish($form->fresh());
        $this->get(route('public-forms.show', $form->slug))->assertStatus(410);
        $this->putJson(route('forms.update', $form), $this->payload($form, [$this->field()], ['deadline' => now()->subDay()->toIso8601String()]))->assertOk();
        $form = $this->publish($form->fresh());
        $this->get(route('public-forms.show', $form->slug))->assertStatus(410)->assertSee('deadline');
    }

    public function test_remaining_field_types_validate_and_defaults_are_checked(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, [
            $this->field('section', 'heading', ['required' => false]),
            $this->field('instructions', 'description', ['required' => false]),
            $this->field('notes', 'textarea', ['validation_rules' => ['min_length' => 3, 'max_length' => 50]]),
            $this->field('contact', 'phone'), $this->field('visit', 'date'), $this->field('start', 'time'),
            $this->field('consent', 'boolean'),
        ]));
        $this->get(route('public-forms.show', $form->slug))->assertOk()->assertSee('Instructions');
        $this->submit($form, ['notes' => 'Useful notes', 'contact' => '+1 876 555 0100', 'visit' => '2026-11-05', 'start' => '09:30', 'consent' => '1'])->assertRedirect();
        $this->assertTrue($form->submissions()->sole()->data['consent']);
        $this->assertArrayNotHasKey('section', $form->submissions()->sole()->data);
        $this->putJson(route('forms.update', $form), $this->payload($form, [$this->field('email', 'email', ['default_value' => 'not-an-email'])]))->assertUnprocessable()->assertJsonValidationErrors('fields.0.default_value');
    }

    public function test_analytics_aggregate_real_numeric_and_choice_answers(): void
    {
        $owner = User::factory()->create();
        $form = $this->publish($this->makeForm($owner, [$this->field('age', 'number'), $this->field('parish', 'select', ['options' => ['Kingston', 'Clarendon']]), $this->field('crops', 'checkboxes', ['options' => ['Tomatoes', 'Carrots']])]));
        $this->submit($form, ['age' => 10, 'parish' => 'Kingston', 'crops' => ['Tomatoes', 'Carrots']])->assertRedirect();
        $this->submit($form, ['age' => 30, 'parish' => 'Kingston', 'crops' => ['Tomatoes']])->assertRedirect();
        $analytics = app(FormReportService::class)->analytics($form);
        $this->assertSame(2, $analytics['total']);
        $this->assertSame(2, array_sum(array_column($analytics['days'], 'total')));
        $this->assertEquals(20, $analytics['numeric'][0]['stats']['average']);
        $this->assertSame(['label' => 'Kingston', 'total' => 2], $analytics['distributions'][0]['values'][0]);
        $this->assertSame([2, 1], array_column($analytics['distributions'][1]['values'], 'total'));
    }

    public function test_public_submission_rate_limits_and_honeypots_are_enforced(): void
    {
        $form = $this->publish($this->makeForm(User::factory()->create()));
        $this->postJson(route('public-forms.submit', $form->slug), ['version_id' => $form->current_published_version_id, 'answers' => ['full_name' => 'Bot'], 'website' => 'spam'])->assertUnprocessable()->assertJsonValidationErrors('website');
        for ($attempt = 0; $attempt < 19; $attempt++) {
            $this->submit($form, [])->assertUnprocessable();
        }
        $this->submit($form, ['full_name' => 'Limited'])->assertStatus(429);
        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_canonical_fingerprints_ignore_object_key_order_but_preserve_question_order(): void
    {
        $schemas = app(FormSchemaService::class);
        $one = ['title' => 'Example', 'fields' => [['label' => 'A', 'type' => 'text'], ['label' => 'B', 'type' => 'text']]];
        $reorderedObjects = ['fields' => [['type' => 'text', 'label' => 'A'], ['type' => 'text', 'label' => 'B']], 'title' => 'Example'];
        $this->assertSame($schemas->fingerprint($one), $schemas->fingerprint($reorderedObjects));
        $reorderedObjects['fields'] = array_reverse($reorderedObjects['fields']);
        $this->assertNotSame($schemas->fingerprint($one), $schemas->fingerprint($reorderedObjects));
    }

    public function test_numeric_conditions_and_custom_validation_messages_are_respected(): void
    {
        $form = $this->publish($this->makeForm(User::factory()->create(), [$this->field('rating', 'number'), $this->field('reason', 'text', ['conditional_rules' => ['field' => 'rating', 'operator' => 'equals', 'value' => '1.0'], 'validation_rules' => ['message' => 'Please explain your rating.']])]));
        $response = $this->submit($form, ['rating' => '1.0'])->assertUnprocessable()->assertJsonValidationErrors('answers.reason');
        $this->assertSame('Please explain your rating.', $response->json('errors')['answers.reason'][0]);
    }
}

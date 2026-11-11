<?php

use App\Models\Form;
use App\Models\User;
use App\Services\Forms\FormPublishingService;
use App\Services\Forms\FormReportService;
use App\Services\Forms\FormSchemaService;
use App\Services\Forms\FormService;
use App\Services\Forms\FormSubmissionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment(['local', 'testing']) || DB::connection()->getDriverName() !== 'mysql') {
    fwrite(STDERR, "This rollback-only smoke check requires a local/testing MySQL connection.\n");
    exit(2);
}

$assert = function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$before = Form::withTrashed()->count();
DB::beginTransaction();
try {
    $owner = User::query()->create(['name' => 'Form module smoke test', 'email' => 'form-smoke-'.Str::uuid().'@example.invalid', 'password' => Str::random(40)]);
    $service = app(FormService::class);
    $schemas = app(FormSchemaService::class);
    $publishing = app(FormPublishingService::class);
    $submissions = app(FormSubmissionService::class);
    $reports = app(FormReportService::class);
    $field = fn ($key, $type, $extra = []) => array_replace(['field_key' => $key, 'type' => $type, 'label' => ucfirst($key), 'placeholder' => null, 'help_text' => null, 'required' => true, 'options' => [], 'validation_rules' => [], 'conditional_rules' => null, 'default_value' => null], $extra);
    $form = $service->create($owner, 'Farmer Registration');
    $definition = ['title' => $form->title, 'description' => null, 'revision' => 0, 'settings' => $schemas->defaults(), 'fields' => [$field('full_name', 'text'), $field('age', 'number'), $field('parish', 'select', ['options' => ['Kingston', 'Clarendon']]), $field('crops', 'checkboxes', ['options' => ['Tomatoes', 'Carrots']])]];
    $form = $service->update($form, $definition);
    $version = $publishing->publish($form, $owner, $form->revision);
    $form->refresh();
    $same = $publishing->publish($form, $owner, $form->revision);
    $assert($same->id === $version->id, 'An unchanged MySQL JSON definition incorrectly generated a new version.');
    $submission = $submissions->submit($form->fresh(), $version->id, ['full_name' => 'John Brown', 'age' => '35', 'parish' => 'Kingston', 'crops' => ['Tomatoes', 'Carrots']], null);
    $assert($submission->fresh()->data['age'] === 35, 'MySQL did not retain numeric JSON typing.');
    $analytics = $reports->analytics($form);
    $assert($analytics['total'] === 1 && (float) $analytics['numeric'][0]['stats']['average'] === 35.0, 'MySQL numeric aggregation failed.');
    $assert(array_column($analytics['distributions'][1]['values'], 'total') === [1, 1], 'MySQL checkbox aggregation failed.');
    $copy = $service->duplicate($form, $owner);
    $assert($copy->status === 'draft' && $copy->submissions()->count() === 0 && $copy->current_published_version_id === null, 'Duplicate form isolation failed.');
    $definition['title'] = 'Farmer Registration Revised';
    $definition['revision'] = $form->fresh()->revision;
    $form = $service->update($form, $definition);
    $newVersion = $publishing->publish($form, $owner, $form->revision);
    $assert($newVersion->version_number === 2 && $submission->fresh()->form_version_id === $version->id, 'Historical version preservation failed.');
    echo json_encode(['driver' => 'mysql', 'unchanged_version_reused' => true, 'typed_json' => true, 'analytics' => true, 'duplication' => true, 'historical_versions' => true], JSON_THROW_ON_ERROR)."\n";
} finally {
    DB::rollBack();
}
$assert(Form::withTrashed()->count() === $before, 'Smoke-test data was not rolled back.');
echo "Temporary smoke-test records rolled back.\n";

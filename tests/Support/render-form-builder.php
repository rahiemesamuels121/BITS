<?php

use App\Models\User;
use App\Services\Forms\FormSchemaService;
use App\Services\Forms\FormService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// This fixture never connects to the application's configured database.
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'app.url' => 'http://localhost:8000']);
DB::purge('sqlite');
if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getConfig('database') !== ':memory:') {
    throw new RuntimeException('The builder fixture requires isolated in-memory SQLite.');
}
Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
$owner = User::factory()->create();
Auth::login($owner);
$form = app(FormService::class)->create($owner, 'Builder interaction test');
$schemas = app(FormSchemaService::class);
if (in_array('--existing', $argv, true)) {
    $form = app(FormService::class)->update($form, [
        'title' => $form->title,
        'description' => null,
        'revision' => $form->revision,
        'settings' => $schemas->settings($form->draft_settings),
        'fields' => [
            ['field_key' => 'q_name', 'label' => 'Original name', 'type' => 'text', 'required' => false, 'options' => [], 'validation_rules' => []],
            ['field_key' => 'q_service', 'label' => 'Service', 'type' => 'select', 'required' => false, 'options' => ['Agency', 'Admin'], 'default_value' => 'Agency', 'validation_rules' => []],
        ],
    ]);
}
request()->setRouteResolver(fn () => app('router')->getRoutes()->getByName('forms.edit'));
view()->share('errors', new ViewErrorBag);
echo view('forms.builder', ['form' => $form, 'definition' => $schemas->definition($form), 'settings' => $schemas->settings($form->draft_settings)])->render();

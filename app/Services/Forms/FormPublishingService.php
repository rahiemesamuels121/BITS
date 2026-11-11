<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FormPublishingService
{
    public function __construct(private FormSchemaService $schemas) {}

    public function publish(Form $form, User $publisher, int $revision): FormVersion
    {
        return DB::transaction(function () use ($form, $publisher, $revision) {
            $form = Form::query()->lockForUpdate()->findOrFail($form->id);
            abort_if($revision !== $form->revision, 409, 'The draft changed. Reload the builder before publishing.');
            $schema = $this->schemas->definition($form);
            $this->schemas->check($schema, true);
            $settings = $this->schemas->settings($form->draft_settings ?? []);
            $version = $form->publishedVersion;
            if (! $version || $this->schemas->fingerprint($version->schema_json) !== $this->schemas->fingerprint($schema) || $this->schemas->fingerprint($version->settings_json) !== $this->schemas->fingerprint($settings)) {
                $version = $form->versions()->create(['version_number' => ($form->versions()->max('version_number') ?? 0) + 1, 'schema_json' => $schema, 'settings_json' => $settings, 'published_by' => $publisher->id, 'published_at' => now(), 'created_at' => now()]);
            }
            $form->current_published_version_id = $version->id;
            $form->status = 'published';
            $form->save();

            return $version;
        });
    }

    public function stop(Form $form, bool $archive = false): void
    {
        DB::transaction(function () use ($form, $archive) {
            $form = Form::query()->lockForUpdate()->findOrFail($form->id);
            $form->status = $archive ? 'archived' : 'draft';
            $form->save();
        });
    }
}

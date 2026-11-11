<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormService
{
    public function __construct(private FormSchemaService $schemas) {}

    public function create(User $owner, string $title, ?string $description = null): Form
    {
        return $owner->forms()->create(['title' => $title, 'description' => $description, 'slug' => $this->slug($title), 'status' => 'draft', 'revision' => 0, 'draft_settings' => $this->schemas->defaults()]);
    }

    public function update(Form $form, array $definition): Form
    {
        $definition['fields'] = array_map(fn (array $field) => array_replace($field, ['conditional_rules' => ($field['conditional_rules'] ?? []) ?: null]), $definition['fields']);
        $this->schemas->check($definition);

        return DB::transaction(function () use ($form, $definition) {
            $form = Form::query()->lockForUpdate()->findOrFail($form->id);
            abort_if($form->revision !== (int) $definition['revision'], 409, 'This form changed in another window. Reload before saving to avoid overwriting someone else’s changes.');
            $form->fill(['title' => $definition['title'], 'description' => $definition['description'] ?? null, 'draft_settings' => $this->schemas->prepareSettings($definition['settings']), 'revision' => $form->revision + 1])->save();
            $form->fields()->delete();
            foreach ($definition['fields'] as $index => $field) {
                $form->fields()->create(array_merge($field, ['sort_order' => $index]));
            }

            return $form->fresh();
        });
    }

    public function duplicate(Form $source, User $owner): Form
    {
        return DB::transaction(function () use ($source, $owner) {
            $source = Form::query()->lockForUpdate()->findOrFail($source->id);
            $copy = $this->create($owner, Str::limit($source->title, 245, '').' (copy)', $source->description);
            $copy->draft_settings = $source->draft_settings;
            $copy->save();
            foreach ($source->fields as $field) {
                $copy->fields()->create($field->only(['field_key', 'label', 'type', 'placeholder', 'help_text', 'required', 'options', 'validation_rules', 'conditional_rules', 'default_value', 'sort_order']));
            }

            return $copy;
        });
    }

    private function slug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 150, '') ?: 'form';
        do {
            $slug = $base.'-'.Str::lower(Str::random(8));
        } while (Form::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}

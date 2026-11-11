<?php

namespace App\Services\Forms;

use App\Models\Form;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    public function __construct(private FormValidationService $validation) {}

    public const TYPES = ['text', 'textarea', 'number', 'email', 'phone', 'date', 'time', 'select', 'radio', 'checkboxes', 'boolean', 'file', 'heading', 'description'];

    public const FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'csv', 'docx', 'xlsx'];

    public const PRESENTATION = ['heading', 'description'];

    public function defaults(): array
    {
        return ['confirmation_message' => 'Thank you. Your response has been recorded.', 'submit_text' => 'Submit response', 'active' => true, 'allow_anonymous' => true, 'require_authentication' => false, 'accept_responses' => true, 'deadline' => null, 'max_submissions' => null, 'one_per_user' => false, 'show_title' => true, 'accent' => 'indigo', 'logo_url' => null, 'spacing' => 'comfortable', 'layout' => 'single'];
    }

    public function settings(array $settings = []): array
    {
        return array_replace($this->defaults(), $settings);
    }

    public function definition(Form $form): array
    {
        return ['title' => $form->title, 'description' => $form->description, 'fields' => $form->fields()->get()->map(fn ($field) => $field->only(['field_key', 'label', 'type', 'placeholder', 'help_text', 'required', 'options', 'validation_rules', 'conditional_rules', 'default_value']))->all()];
    }

    public function check(array $definition, bool $publishing = false): void
    {
        $errors = [];
        $previous = [];
        $questions = 0;
        foreach ($definition['fields'] as $index => $field) {
            $prefix = "fields.$index";
            $type = $field['type'];
            if (! in_array($type, self::PRESENTATION, true)) {
                $questions++;
            }
            $options = $field['options'] ?? [];
            $rules = $field['validation_rules'] ?? [];
            if (count($options) !== count(array_unique($options))) {
                $errors["$prefix.options"] = 'Answer options must be unique.';
            }
            if (in_array($type, ['select', 'radio', 'checkboxes'], true) && $publishing && count($options) === 0) {
                $errors["$prefix.options"] = 'Add at least one answer option before publishing.';
            }
            foreach (['min' => 'max', 'min_length' => 'max_length'] as $minimum => $maximum) {
                if (isset($rules[$minimum], $rules[$maximum]) && $rules[$minimum] > $rules[$maximum]) {
                    $errors["$prefix.validation_rules.$maximum"] = 'The maximum must be at least the minimum.';
                }
            }
            $default = $field['default_value'] ?? null;
            if ($type === 'file' && $default !== null) {
                $errors["$prefix.default_value"] = 'File fields cannot have a default upload.';
            }
            if ($type === 'file' && $publishing && isset($rules['allowed_types']) && $rules['allowed_types'] === []) {
                $errors["$prefix.validation_rules.allowed_types"] = 'Select at least one allowed file type.';
            }
            if (is_array($default) && ($type !== 'checkboxes' || count(array_filter($default, 'is_string')) !== count($default))) {
                $errors["$prefix.default_value"] = 'Only checkbox fields support an array of default values.';
            }
            if ($default !== null && ! is_array($default) && ! is_scalar($default)) {
                $errors["$prefix.default_value"] = 'Use a text, numeric, boolean, or checkbox default.';
            }
            if ($default !== null && strlen(json_encode($default)) > 10000) {
                $errors["$prefix.default_value"] = 'The default value is too long.';
            }
            if (in_array($type, ['select', 'radio', 'checkboxes'], true) && $default !== null && $default !== '') {
                foreach (is_array($default) ? $default : [$default] as $value) {
                    if (! in_array($value, $options, true)) {
                        $errors["$prefix.default_value"] = 'Default selections must match the configured options.';
                    }
                }
            }
            if ($default !== null && $default !== '' && $type !== 'file' && ! in_array($type, self::PRESENTATION, true) && ! isset($errors["$prefix.default_value"])) {
                try {
                    $example = array_replace($field, ['required' => false, 'conditional_rules' => null]);
                    $this->validation->validate(['fields' => [$example]], [$field['field_key'] => $default]);
                } catch (ValidationException $exception) {
                    $errors["$prefix.default_value"] = 'The default value must satisfy this question’s validation rules.';
                }
            }
            $condition = $field['conditional_rules'] ?? null;
            if ($condition) {
                $source = $previous[$condition['field']] ?? null;
                if (! $source || in_array($source['type'], self::PRESENTATION, true) || $source['type'] === 'file') {
                    $errors["$prefix.conditional_rules.field"] = 'Conditions must refer to an earlier answer field. Move the controlling question above this field.';
                }
                if ($condition['operator'] === 'contains' && $source && ! in_array($source['type'], ['text', 'textarea', 'email', 'phone', 'checkboxes'], true)) {
                    $errors["$prefix.conditional_rules.operator"] = 'Contains is available for text and checkbox answers.';
                }
                if ($source && in_array($source['type'], ['select', 'radio', 'checkboxes'], true) && ! in_array((string) $condition['value'], array_map('strval', $source['options'] ?? []), true)) {
                    $errors["$prefix.conditional_rules.value"] = 'Choose a condition value from the controlling question’s options.';
                }
                if ($source && $source['type'] === 'boolean' && ! in_array($condition['value'], [true, false, 0, 1, '0', '1'], true)) {
                    $errors["$prefix.conditional_rules.value"] = 'Choose Yes or No for a boolean condition.';
                }
                if ($source && $source['type'] === 'number' && ! is_numeric($condition['value'])) {
                    $errors["$prefix.conditional_rules.value"] = 'Use a numeric value for this condition.';
                }
            }
            $previous[$field['field_key']] = $field;
        }
        if ($publishing && $questions === 0) {
            $errors['fields'] = 'Add at least one question before publishing.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function prepareSettings(array $settings): array
    {
        $settings = $this->settings($settings);
        foreach (['active', 'allow_anonymous', 'require_authentication', 'accept_responses', 'one_per_user', 'show_title'] as $key) {
            $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
        }
        if ($settings['max_submissions'] !== null) {
            $settings['max_submissions'] = (int) $settings['max_submissions'];
        }
        if ($settings['deadline']) {
            $settings['deadline'] = CarbonImmutable::parse($settings['deadline'])->utc()->toIso8601String();
        }

        return $settings;
    }

    public function fingerprint(array $value): string
    {
        // MySQL JSON can reorder object keys; field/option list order remains significant.
        $canonical = function (mixed $item) use (&$canonical): mixed {
            if (! is_array($item)) {
                return $item;
            }
            if (! array_is_list($item)) {
                ksort($item);
            }

            return array_map($canonical, $item);
        };

        return hash('sha256', json_encode($canonical($value), JSON_THROW_ON_ERROR));
    }
}

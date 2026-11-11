<?php

namespace App\Services\Forms;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class FormValidationService
{
    public function __construct(private FormConditionService $conditions, private FormOptionSourceService $optionSources) {}

    public function validate(array $schema, array $answers): array
    {
        $fields = $schema['fields'];
        $keys = array_column(array_filter($fields, fn ($field) => ! in_array($field['type'], FormSchemaService::PRESENTATION, true)), 'field_key');
        if (array_diff(array_keys($answers), $keys)) {
            throw ValidationException::withMessages(['answers' => 'The response contains unknown questions. Refresh the form and try again.']);
        }
        $visible = [];
        $normalized = [];
        foreach ($fields as $field) {
            $key = $field['field_key'];
            $visible[$key] = $this->conditions->visible($field, $answers, $visible);
            if (! $visible[$key] || in_array($field['type'], FormSchemaService::PRESENTATION, true)) {
                continue;
            }
            $value = $answers[$key] ?? null;
            $rules = $field['validation_rules'] ?? [];
            $required = ($field['required'] ?? false) ? 'required' : 'nullable';
            $constraints = [$required];
            $type = $field['type'];
            $options = $field['options'] ?? [];
            $constraints = array_merge($constraints, match ($type) {
                'number' => ['numeric', 'min:'.($rules['min'] ?? -1000000000000000), 'max:'.($rules['max'] ?? 1000000000000000)],
                'email' => ['string', 'email', 'min:'.($rules['min_length'] ?? 0), 'max:'.min($rules['max_length'] ?? 254, 254)],
                'phone' => ['string', 'regex:/^[+0-9() .\-]{5,50}$/', 'min:'.($rules['min_length'] ?? 5), 'max:'.min($rules['max_length'] ?? 50, 50)],
                'date' => ['date_format:Y-m-d'],
                'time' => ['date_format:H:i'],
                'boolean' => ['boolean'],
                'select' => ($field['option_source'] ?? null) ? ['string'] : ['string', Rule::in($options)],
                'radio' => ['string', Rule::in($options)],
                'checkboxes' => ['array', 'max:100'],
                'file' => [File::types(($rules['allowed_types'] ?? []) ?: ['pdf', 'jpg', 'png'])->max($rules['max_size'] ?? 2048)],
                default => ['string', 'min:'.($rules['min_length'] ?? 0), 'max:'.($rules['max_length'] ?? ($type === 'textarea' ? 10000 : 1000))],
            });
            $validation = ["answers.$key" => $constraints];
            if ($type === 'checkboxes') {
                $validation["answers.$key.*"] = ['string', Rule::in($options), 'distinct'];
            }
            $messages = [];
            if (! empty($rules['message'])) {
                $messages["answers.$key.*"] = $rules['message'];
            }
            Validator::make(['answers' => [$key => $value]], $validation, $messages, ["answers.$key" => $field['label']])->validate();
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if ($type === 'select' && ($field['option_source'] ?? null) && ! $this->optionSources->contains($field['option_source'], $value)) {
                throw ValidationException::withMessages(["answers.$key" => 'Choose an option that is currently available.']);
            }
            if ($type === 'file') {
                $allowed = ($rules['allowed_types'] ?? []) ?: ['pdf', 'jpg', 'png'];
                if (! $value instanceof UploadedFile || ! in_array(strtolower($value->getClientOriginalExtension()), $allowed, true)) {
                    throw ValidationException::withMessages(["answers.$key" => 'This file extension is not allowed.']);
                }
            }
            $normalized[$key] = match ($type) {
                'number' => preg_match('/^-?\d+$/', (string) $value) ? (int) $value : (float) $value,
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'checkboxes' => array_values($value),
                default => $value,
            };
            $answers[$key] = $normalized[$key];
        }

        return $normalized;
    }
}

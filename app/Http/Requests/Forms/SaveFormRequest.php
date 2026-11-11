<?php

namespace App\Http\Requests\Forms;

use App\Models\Form;
use App\Services\Forms\FormSchemaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('form') ? $this->user()->can('update', $this->route('form')) : $this->user()->can('create', Form::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'revision' => ['required', 'integer', 'min:0'],
            'fields' => ['present', 'array', 'max:200'],
            'fields.*' => ['array:field_key,label,type,placeholder,help_text,required,options,validation_rules,conditional_rules,default_value'],
            'fields.*.field_key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,63}$/', 'distinct'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::in(FormSchemaService::TYPES)],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.help_text' => ['nullable', 'string', 'max:2000'],
            'fields.*.required' => ['required', 'boolean'],
            'fields.*.options' => ['nullable', 'array', 'max:100'],
            'fields.*.options.*' => ['required', 'string', 'max:200'],
            'fields.*.default_value' => ['nullable'],
            'fields.*.validation_rules' => ['nullable', 'array:min,max,min_length,max_length,allowed_types,max_size,message'],
            'fields.*.validation_rules.min' => ['nullable', 'numeric', 'between:-1000000000000000,1000000000000000'],
            'fields.*.validation_rules.max' => ['nullable', 'numeric', 'between:-1000000000000000,1000000000000000'],
            'fields.*.validation_rules.min_length' => ['nullable', 'integer', 'between:0,100000'],
            'fields.*.validation_rules.max_length' => ['nullable', 'integer', 'between:1,100000'],
            'fields.*.validation_rules.allowed_types' => ['nullable', 'array', 'max:10'],
            'fields.*.validation_rules.allowed_types.*' => [Rule::in(FormSchemaService::FILE_TYPES)],
            'fields.*.validation_rules.max_size' => ['nullable', 'integer', 'between:1,10240'],
            'fields.*.validation_rules.message' => ['nullable', 'string', 'max:255'],
            'fields.*.conditional_rules' => ['nullable', 'array:field,operator,value'],
            'fields.*.conditional_rules.field' => ['required_with:fields.*.conditional_rules', 'string', 'max:64'],
            'fields.*.conditional_rules.operator' => ['required_with:fields.*.conditional_rules', Rule::in(['equals', 'not_equals', 'contains'])],
            'fields.*.conditional_rules.value' => ['required_with:fields.*.conditional_rules', function ($attribute, $value, $fail) {
                if (! is_scalar($value) || strlen((string) $value) > 1000) {
                    $fail('Use a short text, number, or boolean condition value.');
                }
            }],
            'settings' => ['required', 'array:confirmation_message,submit_text,active,allow_anonymous,require_authentication,accept_responses,deadline,max_submissions,one_per_user,show_title,accent,logo_url,spacing,layout'],
            'settings.confirmation_message' => ['required', 'string', 'max:2000'],
            'settings.submit_text' => ['required', 'string', 'max:80'],
            'settings.active' => ['required', 'boolean'],
            'settings.allow_anonymous' => ['required', 'boolean'],
            'settings.require_authentication' => ['required', 'boolean'],
            'settings.accept_responses' => ['required', 'boolean'],
            'settings.one_per_user' => ['required', 'boolean'],
            'settings.show_title' => ['required', 'boolean'],
            'settings.deadline' => ['nullable', 'date'],
            'settings.max_submissions' => ['nullable', 'integer', 'between:1,1000000000'],
            'settings.accent' => ['required', Rule::in(['indigo', 'lime', 'blue'])],
            'settings.logo_url' => ['nullable', 'url:https', 'max:1000'],
            'settings.spacing' => ['required', Rule::in(['compact', 'comfortable'])],
            'settings.layout' => ['required', Rule::in(['single', 'two_column'])],
        ];
    }
}

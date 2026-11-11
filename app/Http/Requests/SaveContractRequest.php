<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-contracts') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'client_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')->whereIn('role_id', Role::query()->where('code', Role::AGENCY)->select('id'))],
            'form_id' => ['nullable', 'integer', Rule::exists('forms', 'id')->whereNull('deleted_at'), Rule::unique('contracts', 'form_id')->ignore($this->route('contract')?->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'Choose an existing client account.',
            'form_id.exists' => 'Choose an existing form.',
            'form_id.unique' => 'This form is already attached to another contract.',
        ];
    }
}

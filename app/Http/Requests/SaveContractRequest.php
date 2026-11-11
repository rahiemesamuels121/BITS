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

    protected function prepareForValidation(): void
    {
        $contract = $this->route('contract');
        $this->mergeIfMissing([
            'is_active' => $contract?->is_active ?? true,
            'is_indefinite' => $contract?->is_indefinite ?? true,
            'starts_at' => $contract?->starts_at?->toDateString(),
            'ends_at' => $contract?->ends_at?->toDateString(),
        ]);

        // Unchecked rows may still be submitted when JavaScript is unavailable.
        $assignments = $this->input('client_assignments');
        $clientIds = $this->input('client_ids') ?? [];
        if (is_array($assignments) && is_array($clientIds)) {
            $selectedIds = array_filter($clientIds, fn ($id) => is_string($id) || is_int($id));
            $this->merge(['client_assignments' => array_intersect_key($assignments, array_fill_keys($selectedIds, true))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $assignedClientIds = $this->route('contract')?->clients()->allRelatedIds()->all() ?? [];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_indefinite' => ['required', 'boolean'],
            'starts_at' => [Rule::excludeIf($this->boolean('is_indefinite')), Rule::requiredIf($this->has('is_indefinite') && ! $this->boolean('is_indefinite')), 'nullable', 'date_format:Y-m-d'],
            'ends_at' => [Rule::excludeIf($this->boolean('is_indefinite')), Rule::requiredIf($this->has('is_indefinite') && ! $this->boolean('is_indefinite')), 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'description' => ['nullable', 'string', 'max:10000'],
            'client_ids' => ['nullable', 'array'],
            'client_assignments' => ['nullable', 'array'],
            'client_assignments.*' => ['required', 'array:duration,starts_at,ends_at'],
            'client_assignments.*.duration' => ['required', Rule::in(['contract', 'custom'])],
            'client_ids.*' => [
                'required', 'integer', 'distinct',
                Rule::exists('users', 'id')->whereNull('deleted_at')->whereIn('role_id', Role::query()->where('code', Role::AGENCY)->select('id')),
                Rule::unique('contract_user', 'user_id')->whereNotIn('user_id', $assignedClientIds),
            ],
            'form_id' => ['nullable', 'integer', Rule::exists('forms', 'id')->whereNull('deleted_at'), Rule::unique('contracts', 'form_id')->ignore($this->route('contract')?->id)],
        ];

        $assignments = $this->input('client_assignments', []);
        foreach (is_array($assignments) ? $assignments : [] as $clientId => $assignment) {
            if (! ctype_digit((string) $clientId)) {
                continue;
            }
            $custom = is_array($assignment) && ($assignment['duration'] ?? null) === 'custom';
            $prefix = 'client_assignments.'.$clientId;
            $rules[$prefix.'.starts_at'] = [Rule::excludeIf(! $custom), Rule::requiredIf($custom), 'date_format:Y-m-d'];
            $rules[$prefix.'.ends_at'] = [Rule::excludeIf(! $custom), Rule::requiredIf($custom), 'date_format:Y-m-d', 'after_or_equal:'.$prefix.'.starts_at'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'client_ids.*.exists' => 'Choose an existing client account.',
            'client_ids.*.unique' => 'This client is already assigned to another contract.',
            'form_id.exists' => 'Choose an existing form.',
            'form_id.unique' => 'This form is already attached to another contract.',
        ];
    }
}

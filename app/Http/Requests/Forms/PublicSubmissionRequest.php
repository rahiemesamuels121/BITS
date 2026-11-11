<?php

namespace App\Http\Requests\Forms;

use Illuminate\Foundation\Http\FormRequest;

class PublicSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication requirements are read from the immutable published settings.
        return true;
    }

    public function rules(): array
    {
        return ['version_id' => ['required', 'integer'], 'answers' => ['sometimes', 'array', 'max:200'], 'website' => ['nullable', 'string', 'max:0']];
    }
}

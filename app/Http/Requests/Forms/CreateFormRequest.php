<?php

namespace App\Http\Requests\Forms;

use App\Models\Form;
use Illuminate\Foundation\Http\FormRequest;

class CreateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Form::class);
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']];
    }
}

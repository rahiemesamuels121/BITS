<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['field_key', 'label', 'type', 'placeholder', 'help_text', 'required', 'options', 'option_labels', 'option_source', 'validation_rules', 'conditional_rules', 'default_value', 'sort_order'])]
class FormField extends Model
{
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'options' => 'array',
            'option_labels' => 'array',
            'option_source' => 'array',
            'validation_rules' => 'array',
            'conditional_rules' => 'array',
            'default_value' => 'json',
            'sort_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['form_version_id', 'user_id', 'data', 'submitted_at'])]
class FormSubmission extends Model
{
    protected function casts(): array
    {
        return ['data' => 'array', 'submitted_at' => 'datetime'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

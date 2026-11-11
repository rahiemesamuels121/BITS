<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['version_number', 'schema_json', 'settings_json', 'published_by', 'published_at', 'created_at'])]
class FormVersion extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['schema_json' => 'array', 'settings_json' => 'array', 'published_at' => 'datetime', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Published versions are immutable. Publish a new version instead.'));
        static::deleting(fn () => throw new LogicException('Published versions are retained for historical submissions.'));
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}

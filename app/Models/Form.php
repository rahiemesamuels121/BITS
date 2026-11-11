<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'slug', 'status', 'draft_settings', 'revision'])]
class Form extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['draft_settings' => 'array', 'revision' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'current_published_version_id');
    }
}

<?php

namespace App\Models;

use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description', 'form_id', 'is_active', 'is_indefinite', 'starts_at', 'ends_at'])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    protected $attributes = ['is_active' => true, 'is_indefinite' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_indefinite' => 'boolean', 'starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(ContractAssignment::class)
            ->withPivot(['uses_contract_duration', 'starts_at', 'ends_at'])->withTrashed();
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class)->withTrashed();
    }
}

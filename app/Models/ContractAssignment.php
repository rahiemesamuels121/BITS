<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractAssignment extends Pivot
{
    protected $table = 'contract_user';

    protected $dateFormat = 'Y-m-d';

    protected $attributes = ['uses_contract_duration' => true];

    protected function casts(): array
    {
        return ['uses_contract_duration' => 'boolean', 'starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function isIndefiniteFor(Contract $contract): bool
    {
        return $this->uses_contract_duration && $contract->is_indefinite;
    }

    public function startsAtFor(Contract $contract): ?CarbonInterface
    {
        return $this->uses_contract_duration
            ? ($contract->is_indefinite ? null : $contract->starts_at)
            : $this->starts_at;
    }

    public function endsAtFor(Contract $contract): ?CarbonInterface
    {
        return $this->uses_contract_duration
            ? ($contract->is_indefinite ? null : $contract->ends_at)
            : $this->ends_at;
    }
}

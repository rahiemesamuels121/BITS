<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveContractRequest;
use App\Models\Contract;
use App\Models\Form;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:200'], 'client_id' => ['nullable', 'integer'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $contracts = Contract::query()->with(['clients', 'form']);
        if (! empty($filters['q'])) {
            $contracts->where('name', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['client_id'])) {
            $contracts->whereHas('clients', fn ($query) => $query->where('users.id', $filters['client_id']));
        }

        if (! empty($filters['status'])) {
            $contracts->where('is_active', $filters['status'] === 'active');
        }

        return view('contracts.index', [
            'contracts' => $contracts->latest()->paginate(15)->withQueryString(),
            'clients' => $this->clients(),
        ]);
    }

    public function create(): View
    {
        return $this->editor(new Contract);
    }

    public function store(SaveContractRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $this->saveContract(new Contract, $request->validated());
        }, 3);

        return to_route('contracts.index')->with('status', 'Contract created.');
    }

    public function edit(Contract $contract): View
    {
        return $this->editor($contract);
    }

    public function update(SaveContractRequest $request, Contract $contract): RedirectResponse
    {
        DB::transaction(function () use ($request, $contract) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $this->saveContract($contract, $request->validated());
        }, 3);

        return to_route('contracts.index')->with('status', 'Contract updated.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return to_route('contracts.index')->with('status', 'Contract removed. Its forms and responses have been retained.');
    }

    private function clients(): Collection
    {
        return User::query()->whereHas('role', fn ($query) => $query->where('code', Role::AGENCY))->orderBy('name')->get();
    }

    private function editor(Contract $contract): View
    {
        $assignedClientIds = $contract->clients->modelKeys();

        return view('contracts.edit', [
            'contract' => $contract,
            'clients' => User::query()->with('role')
                ->where(fn ($query) => $query->doesntHave('contracts')
                    ->when($contract->exists, fn ($query) => $query->orWhereHas('contracts', fn ($query) => $query->where('contracts.id', $contract->id))))
                ->orderBy('name')->get()->sortByDesc(fn ($client) => in_array($client->id, $assignedClientIds, true))->values(),
            'roles' => Role::query()->orderBy('name')->get(),
            'forms' => Form::query()->with('user')->where(fn ($query) => $query->doesntHave('contract')->orWhere('id', $contract->form_id))->orderBy('title')->get(),
        ]);
    }

    private function saveContract(Contract $contract, array $data): void
    {
        $clientIds = array_map('intval', $data['client_ids'] ?? []);
        $currentAssignments = collect();
        if ($clientIds !== []) {
            // Serialize assignment changes for each client, including competing new contracts.
            $clients = User::query()->whereIn('id', $clientIds)->orderBy('id')->lockForUpdate()->get(['id', 'role_id']);
            $agencyRoleId = Role::query()->where('code', Role::AGENCY)->value('id');
            if ($clients->count() !== count($clientIds) || $clients->contains(fn ($client) => (int) $client->role_id !== (int) $agencyRoleId)) {
                throw ValidationException::withMessages(['client_ids' => 'Choose existing client accounts. A selected account is no longer available.']);
            }

            $currentAssignments = $contract->exists
                ? DB::table('contract_user')->where('contract_id', $contract->id)->whereIn('user_id', $clientIds)
                    ->lockForUpdate()->get(['user_id', 'uses_contract_duration', 'starts_at', 'ends_at'])->keyBy('user_id')
                : collect();
            $newClientIds = array_diff($clientIds, $currentAssignments->keys()->all());
            $otherAssignments = DB::table('contract_user')->whereIn('user_id', $newClientIds)
                ->lockForUpdate()->get(['user_id']);
            if ($otherAssignments->isNotEmpty()) {
                throw ValidationException::withMessages(['client_ids' => 'A selected client is already assigned to another contract. Reload the client list and choose an available client.']);
            }
        }

        if (! empty($data['form_id'])) {
            $form = Form::query()->lockForUpdate()->find($data['form_id']);
            if (! $form || Contract::query()->where('form_id', $data['form_id'])->when($contract->exists, fn ($query) => $query->where('id', '!=', $contract->id))->exists()) {
                throw ValidationException::withMessages(['form_id' => 'This form is unavailable or already attached to another contract.']);
            }
        }

        if ((bool) ($data['is_indefinite'] ?? $contract->is_indefinite)) {
            $data['starts_at'] = null;
            $data['ends_at'] = null;
        }

        $assignments = [];
        foreach ($clientIds as $clientId) {
            $settings = $data['client_assignments'][$clientId] ?? null;
            $existing = $currentAssignments->get($clientId);
            $inherits = $settings !== null ? $settings['duration'] === 'contract' : (bool) ($existing->uses_contract_duration ?? true);
            $assignments[$clientId] = [
                'uses_contract_duration' => $inherits,
                'starts_at' => $inherits ? null : ($settings['starts_at'] ?? $existing?->starts_at),
                'ends_at' => $inherits ? null : ($settings['ends_at'] ?? $existing?->ends_at),
            ];
        }

        unset($data['client_ids'], $data['client_assignments']);
        $contract->fill($data)->save();
        $contract->clients()->sync($assignments);
    }
}

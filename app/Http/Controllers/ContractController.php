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
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:200'], 'client_id' => ['nullable', 'integer']]);
        $contracts = Contract::query()->with(['client', 'form']);
        if (! empty($filters['q'])) {
            $contracts->where('name', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['client_id'])) {
            $contracts->where('client_id', $filters['client_id']);
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
        return view('contracts.edit', [
            'contract' => $contract,
            'clients' => $this->clients(),
            'forms' => Form::query()->with('user')->where(fn ($query) => $query->doesntHave('contract')->orWhere('id', $contract->form_id))->orderBy('title')->get(),
        ]);
    }

    private function saveContract(Contract $contract, array $data): void
    {
        if (! empty($data['form_id'])) {
            $form = Form::query()->lockForUpdate()->find($data['form_id']);
            if (! $form || Contract::query()->where('form_id', $data['form_id'])->when($contract->exists, fn ($query) => $query->where('id', '!=', $contract->id))->exists()) {
                throw ValidationException::withMessages(['form_id' => 'This form is unavailable or already attached to another contract.']);
            }
        }

        $contract->fill($data)->save();
    }
}

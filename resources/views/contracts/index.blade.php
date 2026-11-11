@extends('layouts.admin')
@section('title', 'Contracts — BITS')
@section('content')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">CLIENT CONTRACTS</p><h1>Contracts</h1><p>Attach a form to a contract and assign it to multiple clients.</p></div><a class="admin-button admin-button-primary" href="{{ route('contracts.create') }}">Add new contract</a></div>
    <form class="fm-filters" method="GET" action="{{ route('contracts.index') }}">
        <label>Search contracts<input type="search" name="q" value="{{ request('q') }}" maxlength="200" placeholder="Contract name"></label>
        <label>Client<select name="client_id"><option value="">All clients</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>@endforeach</select></label>
        <label>Status<select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></label>
        <button class="admin-button" type="submit">Apply filters</button>
        @if(request()->hasAny(['q', 'client_id', 'status']))<a class="fm-text-link" href="{{ route('contracts.index') }}">Clear</a>@endif
    </form>
    <div class="clients-table-container" role="region" aria-label="Contracts" tabindex="0">
        <table class="clients-table">
            <caption class="sr-only">Contracts matching your filters</caption>
            <thead><tr><th scope="col">Contract</th><th scope="col">Clients</th><th scope="col">Attached form</th><th scope="col">Status / Term</th><th scope="col">Created</th><th scope="col">Actions</th></tr></thead>
            <tbody>
                @forelse($contracts as $contract)
                    <tr>
                        <th scope="row">{{ $contract->name }}</th>
                        <td>@forelse($contract->clients as $client)<div>{{ $client->name }}@if($client->trashed()) <span class="fm-muted">(removed client)</span>@endif<small class="contract-account-email">@if($client->pivot->uses_contract_duration)Uses contract duration @else{{ $client->pivot->startsAtFor($contract)?->format('M j, Y') }} – {{ $client->pivot->endsAtFor($contract)?->format('M j, Y') }}@endif</small></div>@empty Unassigned @endforelse</td>
                        <td>{{ $contract->form?->title ?? 'No form attached' }}@if($contract->form?->trashed()) <span class="fm-muted">(removed form)</span>@endif</td>
                        <td><span class="admin-status {{ $contract->is_active ? 'status-active' : '' }}">{{ $contract->is_active ? 'Active' : 'Inactive' }}</span><small class="contract-account-email">{{ $contract->is_indefinite ? 'Indefinite' : $contract->starts_at?->format('M j, Y').' – '.$contract->ends_at?->format('M j, Y') }}</small></td>
                        <td><time datetime="{{ $contract->created_at->toDateString() }}">{{ $contract->created_at->format('M j, Y') }}</time></td>
                        <td><div class="clients-table-actions">
                            <a class="admin-button" href="{{ route('contracts.edit', $contract) }}" aria-label="Edit {{ $contract->name }}">Edit</a>
                            <form method="POST" action="{{ route('contracts.destroy', $contract) }}" data-confirm="Remove {{ $contract->name }}? Attached forms and their responses will be retained.">@csrf @method('DELETE')<button class="admin-button fm-danger-text" type="submit" aria-label="Remove {{ $contract->name }}">Remove</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="clients-table-empty"><strong>No contracts found.</strong><p>Create a contract or adjust your filters.</p><a class="admin-button admin-button-primary" href="{{ route('contracts.create') }}">Add new contract</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('forms.partials.pagination', ['paginator' => $contracts])
@endsection

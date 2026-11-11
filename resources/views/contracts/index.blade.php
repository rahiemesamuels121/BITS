@extends('layouts.admin')
@section('title', 'Contracts — BITS')
@section('content')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">CLIENT CONTRACTS</p><h1>Contracts</h1><p>Attach a form to a contract and assign it to a client.</p></div><a class="admin-button admin-button-primary" href="{{ route('contracts.create') }}">Add new contract</a></div>
    <form class="fm-filters" method="GET" action="{{ route('contracts.index') }}">
        <label>Search contracts<input type="search" name="q" value="{{ request('q') }}" maxlength="200" placeholder="Contract name"></label>
        <label>Client<select name="client_id"><option value="">All clients</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>@endforeach</select></label>
        <button class="admin-button" type="submit">Apply filters</button>
        @if(request()->hasAny(['q', 'client_id']))<a class="fm-text-link" href="{{ route('contracts.index') }}">Clear</a>@endif
    </form>
    <div class="clients-table-container" role="region" aria-label="Contracts" tabindex="0">
        <table class="clients-table">
            <caption class="sr-only">Contracts matching your filters</caption>
            <thead><tr><th scope="col">Contract</th><th scope="col">Client</th><th scope="col">Attached form</th><th scope="col">Created</th><th scope="col">Actions</th></tr></thead>
            <tbody>
                @forelse($contracts as $contract)
                    <tr>
                        <th scope="row">{{ $contract->name }}</th>
                        <td>{{ $contract->client?->name ?? 'Unassigned' }}@if($contract->client?->trashed()) <span class="fm-muted">(removed client)</span>@endif</td>
                        <td>{{ $contract->form?->title ?? 'No form attached' }}@if($contract->form?->trashed()) <span class="fm-muted">(removed form)</span>@endif</td>
                        <td><time datetime="{{ $contract->created_at->toDateString() }}">{{ $contract->created_at->format('M j, Y') }}</time></td>
                        <td><div class="clients-table-actions">
                            <a class="admin-button" href="{{ route('contracts.edit', $contract) }}" aria-label="Edit {{ $contract->name }}">Edit</a>
                            <form method="POST" action="{{ route('contracts.destroy', $contract) }}" data-confirm="Remove {{ $contract->name }}? Attached forms and their responses will be retained.">@csrf @method('DELETE')<button class="admin-button fm-danger-text" type="submit" aria-label="Remove {{ $contract->name }}">Remove</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="clients-table-empty"><strong>No contracts found.</strong><p>Create a contract or adjust your filters.</p><a class="admin-button admin-button-primary" href="{{ route('contracts.create') }}">Add new contract</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('forms.partials.pagination', ['paginator' => $contracts])
@endsection

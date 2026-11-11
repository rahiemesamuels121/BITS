@extends('layouts.admin')
@section('title', 'Clients — BITS')
@section('content')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">CLIENT ACCOUNTS</p><h1>Clients</h1><p>Create accounts and manage client access to BITS.</p></div><a class="admin-button admin-button-primary" href="{{ route('clients.create') }}">Add new client</a></div>
    <form class="fm-filters" method="GET" action="{{ route('clients.index') }}">
        <label>Search clients<input type="search" name="q" value="{{ request('q') }}" maxlength="200" placeholder="Name or email"></label>
        <label>Status<select name="status"><option value="">All accounts</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></label>
        <button class="admin-button" type="submit">Apply filters</button>
        @if(request()->hasAny(['q', 'status']))<a class="fm-text-link" href="{{ route('clients.index') }}">Clear</a>@endif
    </form>
    <div class="clients-table-container" role="region" aria-label="Client accounts" tabindex="0">
        <table class="clients-table">
            <caption class="sr-only">Clients matching your search and status filters</caption>
            <thead><tr><th scope="col">Client name</th><th scope="col">Email address</th><th scope="col">Status</th><th scope="col">Created</th><th scope="col">Actions</th></tr></thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <th scope="row"><div class="pm-client-identity"><span class="pm-client-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}</span><span>{{ $client->name }}</span></div></th><td>{{ $client->email }}</td>
                        <td><span class="fm-status {{ $client->is_active ? 'fm-status-published' : 'fm-status-archived' }}">{{ $client->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td><time datetime="{{ $client->created_at->toDateString() }}">{{ $client->created_at->format('M j, Y') }}</time></td>
                        <td><div class="clients-table-actions">
                            <a class="admin-button" href="{{ route('clients.edit', $client) }}" aria-label="Edit {{ $client->name }}">Edit</a>
                            <form method="POST" action="{{ route('clients.status', $client) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $client->is_active ? '0' : '1' }}"><button class="admin-button" type="submit" aria-label="{{ $client->is_active ? 'Deactivate' : 'Activate' }} {{ $client->name }}">{{ $client->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            <form method="POST" action="{{ route('clients.destroy', $client) }}" data-confirm="Remove {{ $client->name }}? This removes their account access and hides them from the client list. Existing forms and responses will be retained.">@csrf @method('DELETE')<button class="admin-button fm-danger-text" type="submit" aria-label="Remove {{ $client->name }}">Remove</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="clients-table-empty"><strong>No clients found.</strong><p>Create a client or adjust your filters.</p><a class="admin-button admin-button-primary" href="{{ route('clients.create') }}">Add new client</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('forms.partials.pagination', ['paginator' => $clients])
@endsection

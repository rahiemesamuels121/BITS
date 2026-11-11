@extends('layouts.admin')
@section('title', ($contract->exists ? 'Edit Contract' : 'Create Contract').' — BITS')
@section('content')
    <div class="contract-editor-page">
        <a class="fm-text-link contract-back-link" href="{{ route('contracts.index') }}">@include('partials.admin.icon', ['name' => 'back']) Back to contracts</a>
        <div class="admin-page-heading contract-editor-heading">
            <div><p class="admin-eyebrow">CONTRACT WORKSPACE</p><h1>{{ $contract->exists ? 'Edit contract' : 'Create a contract' }}</h1><p>Define the agreement, choose your clients, and set their assignment terms.</p></div>
            <span class="contract-workspace-badge">@include('partials.admin.icon', ['name' => 'shield']) {{ $contract->exists ? 'Existing agreement' : 'New agreement' }}</span>
        </div>
        <div class="fm-create-card contract-editor contract-editor-premium">
            <form method="POST" action="{{ $contract->exists ? route('contracts.update', $contract) : route('contracts.store') }}" class="fm-stack contract-editor-form">
                @csrf
                @if($contract->exists) @method('PUT') @endif
                <section class="contract-section" aria-labelledby="contract-details-title">
                    <div class="contract-section-heading"><span class="contract-section-number">01</span><div><h2 id="contract-details-title">Agreement details</h2><p>A clear name and description for this contract.</p></div></div>
                    <div class="contract-section-body">
                        <label>Contract name<input name="name" value="{{ old('name', $contract->name) }}" required maxlength="255" placeholder="e.g. Work and Travel 2027"></label>
                        <label>Description <span class="contract-optional">Optional</span><textarea name="description" rows="3" maxlength="10000" placeholder="Describe the scope of this agreement…">{{ old('description', $contract->description) }}</textarea></label>
                    </div>
                </section>
                <section class="contract-section" aria-labelledby="contract-term-title">
                    <div class="contract-section-heading"><span class="contract-section-number">02</span><div><h2 id="contract-term-title">Status & duration</h2><p>Set the contract's status and overall term.</p></div></div>
                    <div class="contract-section-body contract-term" data-contract-term>
                        <div class="contract-term-settings">
                            <label>Contract status<select name="is_active" required><option value="1" @selected((string) old('is_active', (int) $contract->is_active) === '1')>Active</option><option value="0" @selected((string) old('is_active', (int) $contract->is_active) === '0')>Inactive</option></select></label>
                            <label>Contract duration<select name="is_indefinite" required data-contract-duration><option value="1" @selected((string) old('is_indefinite', (int) $contract->is_indefinite) === '1')>Indefinite — no end date</option><option value="0" @selected((string) old('is_indefinite', (int) $contract->is_indefinite) === '0')>Start and end dates</option></select></label>
                        </div>
                        <div class="contract-date-fields" data-contract-dates>
                            <label>Start date<input type="date" name="starts_at" value="{{ old('starts_at', $contract->starts_at?->toDateString()) }}"></label>
                            <label>End date<input type="date" name="ends_at" value="{{ old('ends_at', $contract->ends_at?->toDateString()) }}"></label>
                        </div>
                        <p class="contract-section-note">@include('partials.admin.icon', ['name' => 'activity']) Status is managed separately. Dates do not automatically activate or deactivate the contract.</p>
                    </div>
                </section>
                @php($selectedClients = array_map('strval', old('client_ids', session()->hasOldInput() ? [] : $contract->clients->modelKeys()) ?? []))
                <fieldset class="fm-stack contract-assignments contract-section" data-contract-assignments>
                    <legend class="sr-only">Assigned clients</legend>
                    <div class="contract-section-heading"><span class="contract-section-number">03</span><div><h2>Client assignments</h2><p>Select clients and choose the duration of each assignment.</p></div><span class="contract-section-tag">Individual terms</span></div>
                    <div class="contract-assignment-body">
                        <div class="contract-assignment-filters" data-assignment-filters hidden>
                            <label>Search accounts<span class="contract-search-control">@include('partials.admin.icon', ['name' => 'search'])<input type="search" placeholder="Search by name or email…" data-assignment-search></span></label>
                            <label>Role<select data-assignment-role><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach<option value="unassigned">No role</option></select></label>
                        </div>
                        <p class="contract-assignment-guidance">Current assignments appear first. Clients assigned only to other contracts are hidden. Only Agency accounts are eligible.</p>
                        <p class="contract-table-hint">@include('partials.admin.icon', ['name' => 'arrow']) Scroll across to view assignment durations.</p>
                        <div class="clients-table-container" role="region" aria-label="Client assignments" tabindex="0">
                            <table class="clients-table">
                                <caption class="sr-only">Select eligible clients to assign to this contract</caption>
                                <thead><tr><th scope="col">Assign</th><th scope="col">Account</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Assignment duration</th></tr></thead>
                                <tbody>
                                    @foreach($clients as $client)
                                        @php($eligible = $client->role?->code === \App\Models\Role::AGENCY)
                                        @php($assignment = $contract->clients->firstWhere('id', $client->id)?->pivot)
                                        @php($assignmentDuration = old('client_assignments.'.$client->id.'.duration', $assignment && !($assignment->uses_contract_duration ?? true) ? 'custom' : 'contract'))
                                        <tr data-assignment-row data-role="{{ $client->role_id ?? 'unassigned' }}" data-search="{{ mb_strtolower($client->name.' '.$client->email) }}">
                                            <td><input type="checkbox" name="client_ids[]" value="{{ $client->id }}" @checked(in_array((string) $client->id, $selectedClients, true)) @disabled(!$eligible) aria-label="Assign {{ $client->name }}" @if(!$eligible) title="Only Agency accounts can be assigned" @endif></td>
                                            <th scope="row"><div class="contract-client-identity"><span class="contract-client-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}</span><div><strong>{{ $client->name }}</strong><small class="contract-account-email">{{ $client->email }}</small>@if($contract->clients->contains('id', $client->id))<span class="contract-assigned-label">Assigned to this contract</span>@endif @unless($eligible)<small class="fm-muted">Not eligible for assignment</small>@endunless</div></div></th>
                                            <td><span class="contract-role-label">{{ $client->role?->name ?? 'No role' }}</span></td>
                                            <td><span class="admin-status {{ $client->is_active ? 'status-active' : '' }}"><i aria-hidden="true"></i>{{ $client->is_active ? 'Active' : 'Inactive' }}</span></td>
                                            <td class="contract-assignment-duration">
                                                @if($eligible)
                                                    <label><span class="sr-only">Assignment duration for {{ $client->name }}</span><select name="client_assignments[{{ $client->id }}][duration]" data-assignment-duration><option value="contract" @selected($assignmentDuration === 'contract')>Use contract duration</option><option value="custom" @selected($assignmentDuration === 'custom')>Custom dates</option></select></label>
                                                    <div class="contract-assignment-dates" data-assignment-custom-dates>
                                                        <label>Assignment start<input type="date" name="client_assignments[{{ $client->id }}][starts_at]" value="{{ old('client_assignments.'.$client->id.'.starts_at', $assignment?->starts_at?->toDateString()) }}" data-assignment-start></label>
                                                        <label>Assignment end<input type="date" name="client_assignments[{{ $client->id }}][ends_at]" value="{{ old('client_assignments.'.$client->id.'.ends_at', $assignment?->ends_at?->toDateString()) }}" data-assignment-end></label>
                                                    </div>
                                                    <small class="fm-muted" data-assignment-inherited>Follows the contract's duration and future date changes.</small>
                                                @else
                                                    <span class="fm-muted">Select an eligible client.</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-assignment-empty @if($clients->isNotEmpty()) hidden @endif><td colspan="5" class="clients-table-empty">No accounts match your filters.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="contract-assignment-footer"><p class="fm-muted" data-assignment-summary role="status" aria-live="polite">{{ count($selectedClients) }} selected</p><span>Filtering keeps your selections and dates.</span></div>
                    </div>
                </fieldset>
                @if($contract->clients->contains(fn ($client) => $client->trashed()))<p class="fm-muted">A previously assigned client was removed. Saving will remove their assignment.</p>@endif
                <section class="contract-section" aria-labelledby="contract-form-title">
                    <div class="contract-section-heading"><span class="contract-section-number">04</span><div><h2 id="contract-form-title">Attached form</h2><p>Connect the form used for this agreement.</p></div><span class="contract-section-tag">Optional</span></div>
                    <div class="contract-section-body">
                        <label>Attached form<select name="form_id" aria-describedby="contract-form-help"><option value="">No form — attach later</option>@foreach($forms as $form)<option value="{{ $form->id }}" @selected((string) old('form_id', $contract->form_id) === (string) $form->id)>{{ $form->title }} ({{ $form->user?->name ?? 'Removed owner' }})</option>@endforeach</select><span class="fm-muted" id="contract-form-help">One form per contract. Forms attached to other contracts are excluded.</span></label>
                        @if($contract->form?->trashed())<p class="fm-muted">The previously attached form was removed. Choose another form or leave this contract without a form.</p>@endif
                        @if($forms->isEmpty())<p class="fm-muted">No available forms. <a class="fm-text-link" href="{{ route('forms.create') }}">Create a form</a> or detach one from another contract.</p>@endif
                    </div>
                </section>
                <div class="fm-create-actions contract-save-bar"><p>@include('partials.admin.icon', ['name' => 'shield']) Changes apply when you save.</p><div><a class="admin-button" href="{{ route('contracts.index') }}">Cancel</a><button class="admin-button admin-button-primary" type="submit">{{ $contract->exists ? 'Save contract' : 'Create contract' }} @include('partials.admin.icon', ['name' => 'arrow'])</button></div></div>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.admin')
@section('title', ($contract->exists ? 'Edit Contract' : 'Create Contract').' — BITS')
@section('content')
    <a class="fm-text-link" href="{{ route('contracts.index') }}">← Back to contracts</a>
    <div class="fm-create-card">
        <p class="admin-eyebrow">CLIENT CONTRACTS</p><h1>{{ $contract->exists ? 'Edit contract' : 'Create a contract' }}</h1>
        <p>Choose a client and attach one form to this contract.</p>
        <form method="POST" action="{{ $contract->exists ? route('contracts.update', $contract) : route('contracts.store') }}" class="fm-stack">
            @csrf
            @if($contract->exists) @method('PUT') @endif
            <label>Contract name<input name="name" value="{{ old('name', $contract->name) }}" required maxlength="255"></label>
            <label>Description<textarea name="description" rows="4" maxlength="10000">{{ old('description', $contract->description) }}</textarea></label>
            <label>Client<select name="client_id"><option value="">Unassigned — assign later</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((string) old('client_id', $contract->client_id) === (string) $client->id)>{{ $client->name }} ({{ $client->email }}){{ $client->is_active ? '' : ' — inactive' }}</option>@endforeach</select></label>
            @if($contract->client?->trashed())<p class="fm-muted">The previously assigned client was removed. Choose another client or leave this contract unassigned.</p>@endif
            <label>Attached form<select name="form_id" aria-describedby="contract-form-help"><option value="">No form — attach later</option>@foreach($forms as $form)<option value="{{ $form->id }}" @selected((string) old('form_id', $contract->form_id) === (string) $form->id)>{{ $form->title }} ({{ $form->user?->name ?? 'Removed owner' }})</option>@endforeach</select><span class="fm-muted" id="contract-form-help">One form per contract. Forms attached to other contracts are excluded.</span></label>
            @if($contract->form?->trashed())<p class="fm-muted">The previously attached form was removed. Choose another form or leave this contract without a form.</p>@endif
            @if($forms->isEmpty())<p class="fm-muted">No available forms. <a class="fm-text-link" href="{{ route('forms.create') }}">Create a form</a> or detach one from another contract.</p>@endif
            <div class="fm-create-actions"><a class="admin-button" href="{{ route('contracts.index') }}">Cancel</a><button class="admin-button admin-button-primary" type="submit">{{ $contract->exists ? 'Save contract' : 'Create contract' }}</button></div>
        </form>
    </div>
@endsection

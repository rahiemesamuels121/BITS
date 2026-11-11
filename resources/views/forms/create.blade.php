@extends('layouts.admin')
@section('title', 'Create a Form — BITS')
@section('content')
    <a class="fm-text-link" href="{{ route('forms.index') }}">← Back to Forms</a>
    <div class="fm-create-card"><p class="admin-eyebrow">A LITTLE CURIOSITY GOES A LONG WAY</p><h1>Create a new form</h1><p>Give it a name. You can add questions, adjust the appearance, and choose how people respond in the builder.</p><form method="POST" action="{{ route('forms.store') }}" class="fm-stack">@csrf<label>Form title <span aria-hidden="true">*</span><input name="title" value="{{ old('title') }}" required maxlength="255" placeholder="e.g. Farmer Registration" autofocus></label><label>Description<textarea name="description" rows="4" maxlength="10000" placeholder="Tell respondents what this form is for.">{{ old('description') }}</textarea></label><div class="fm-create-actions"><a class="admin-button" href="{{ route('forms.index') }}">Cancel</a><button class="admin-button admin-button-primary" type="submit">Create and start building →</button></div></form></div>
@endsection

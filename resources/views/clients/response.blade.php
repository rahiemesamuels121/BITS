@extends('layouts.admin')
@section('title', 'Response #'.$submission->id.' — BITS')
@section('content')
    <a class="fm-text-link" href="{{ route('clients.responses.index') }}">← Back to my responses</a>
    <div class="admin-page-heading client-response-heading"><div><p class="admin-eyebrow">LINKED TO YOUR ACCOUNT</p><h1>Response #{{ $submission->id }}</h1><p>{{ $submission->version->schema_json['title'] }} · Version {{ $submission->version->version_number }} · {{ $submission->submitted_at->utc()->format('M j, Y H:i') }} UTC</p></div></div>
    <p class="fm-muted">Contract: {{ $submission->form->contract->name }}</p>
    <section class="admin-card fm-response-detail" aria-label="Response answers"><dl>
        @foreach($submission->version->schema_json['fields'] as $field)
            @continue(in_array($field['type'], ['heading', 'description']))
            @php($value = $submission->data[$field['field_key']] ?? null)
            <div><dt>{{ $field['label'] }}</dt><dd>
                @if(is_array($value) && ($value['kind'] ?? null) === 'file')
                    <a class="fm-text-link" href="{{ route('clients.responses.file', [$submission, $field['field_key']]) }}">{{ $value['name'] ?? 'Attachment' }} ↓</a>
                @elseif($field['field_key'] === ($submission->version->settings_json['client_reference_field'] ?? null))
                    {{ $account->name }}
                @else
                    {{ is_scalar($value) ? ($field['option_labels'][(string) $value] ?? $reports->display($value)) : $reports->display($value) }}
                @endif
            </dd></div>
        @endforeach
    </dl></section>
@endsection

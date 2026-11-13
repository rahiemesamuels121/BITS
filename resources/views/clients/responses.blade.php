@extends('layouts.admin')
@section('title', 'My responses — BITS')
@section('content')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">YOUR BITS ACCOUNT</p><h1>My responses</h1></div></div>
    <a class="admin-button" href="{{ route('clients.responses.export', request()->only(['form', 'from', 'to'])) }}">Export for Excel (.csv)</a>
    <form class="fm-filters" method="GET" action="{{ route('clients.responses.index') }}">
        <label>Form<select name="form"><option value="">All forms</option>@foreach($forms as $form)<option value="{{ $form->id }}" @selected((string) request('form') === (string) $form->id)>{{ $form->title }}</option>@endforeach</select></label>
        <label>From (UTC)<input type="date" name="from" value="{{ request('from') }}"></label>
        <label>Through (UTC)<input type="date" name="to" value="{{ request('to') }}"></label>
        <button class="admin-button" type="submit">Apply filters</button><a class="fm-text-link" href="{{ route('clients.responses.index') }}">Clear filters</a>
    </form>
    <section class="client-responses-section" id="my-responses" aria-labelledby="client-responses-title">
        <div class="client-section-heading"><div><p class="admin-eyebrow">FORM RESPONSES</p><h2 id="client-responses-title">Responses linked to you</h2></div><span class="client-contract-count">{{ number_format($responses->total()) }} {{ $responses->total() === 1 ? 'response' : 'responses' }}</span></div>
        <p class="fm-muted">Responses that selected your account on forms attached to your assigned contracts.</p>
        <div class="admin-card client-responses-card">
            <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Response</th><th scope="col">Form / Contract</th><th scope="col">Received (UTC)</th><th scope="col">Details</th></tr></thead><tbody>
                @forelse($responses as $response)
                    <tr><td>#{{ $response->id }}<small class="client-response-meta">Version {{ $response->version->version_number }}</small></td><td>{{ $response->version->schema_json['title'] ?? $response->form->title }}<small class="client-response-meta">{{ $response->form->contract->name }}</small></td><td><time datetime="{{ $response->submitted_at->toIso8601String() }}">{{ $response->submitted_at->utc()->format('M j, Y H:i') }}</time></td><td><a class="fm-text-link" href="{{ route('clients.responses.show', $response) }}">View response →</a></td></tr>
                @empty
                    <tr><td colspan="4"><div class="client-response-empty"><h3>{{ request()->filled('form') || request()->filled('from') || request()->filled('to') ? 'No responses match your filters.' : 'No responses linked to you yet.' }}</h3><p>A response appears here when someone selects your account on an eligible form.</p></div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        @include('forms.partials.pagination', ['paginator' => $responses])
    </section>
@endsection

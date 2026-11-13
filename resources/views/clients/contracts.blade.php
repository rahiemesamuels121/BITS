@extends('layouts.admin')
@section('title', 'My contracts — BITS')
@section('content')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">YOUR BITS ACCOUNT</p><h1>My contracts</h1></div></div>
        <section class="client-contracts-section" id="my-contracts" aria-labelledby="client-contracts-title">
            <div class="client-section-heading"><div><p class="admin-eyebrow">YOUR AGREEMENTS</p><h2 id="client-contracts-title">My contracts</h2></div><span class="client-contract-count">{{ number_format($contracts->total()) }} {{ $contracts->total() === 1 ? 'contract' : 'contracts' }}</span></div>
            <div class="client-contract-list">
                @forelse($contracts as $contract)
                    <article class="admin-card client-contract-card" aria-labelledby="client-contract-{{ $contract->id }}">
                        <div class="client-contract-heading"><span class="client-contract-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'ticket'])</span><div><p class="admin-eyebrow">CONTRACT</p><h3 id="client-contract-{{ $contract->id }}">{{ $contract->name }}</h3></div><span class="admin-status {{ $contract->is_active ? 'status-active' : '' }}">{{ $contract->is_active ? 'Active' : 'Inactive' }}</span></div>
                        @if(filled($contract->description))<p class="client-contract-description">{{ $contract->description }}</p>@endif
                        <dl class="client-contract-term">
                            <div><dt>Duration</dt><dd>{{ $contract->is_indefinite ? 'Indefinite' : 'Dated contract' }}</dd></div>
                            @unless($contract->is_indefinite)
                                <div><dt>Start date</dt><dd>{{ $contract->starts_at?->format('M j, Y') ?? '—' }}</dd></div>
                                <div><dt>End date</dt><dd>{{ $contract->ends_at?->format('M j, Y') ?? '—' }}</dd></div>
                            @endunless
                        </dl>
                        @if($contract->pivot)
                            @php($assignment = $contract->pivot)
                            <section class="client-assignment-details" aria-labelledby="client-assignment-{{ $contract->id }}">
                                <h4 id="client-assignment-{{ $contract->id }}">Your assignment</h4>
                                <p class="fm-muted">{{ $assignment->uses_contract_duration ? 'Uses contract duration' : 'Custom dates' }}</p>
                                <dl class="client-contract-term">
                                    @if($assignment->isIndefiniteFor($contract))
                                        <div><dt>Assignment duration</dt><dd>Indefinite</dd></div>
                                    @else
                                        <div><dt>Assignment start date</dt><dd>{{ $assignment->startsAtFor($contract)?->format('M j, Y') ?? '—' }}</dd></div>
                                        <div><dt>Assignment end date</dt><dd>{{ $assignment->endsAtFor($contract)?->format('M j, Y') ?? '—' }}</dd></div>
                                    @endif
                                </dl>
                            </section>
                        @endif
                        <div class="client-contract-form">
                            @if($contract->form && !$contract->form->trashed())
                                <div><span>Attached form</span><strong>{{ $contract->form->publishedVersion?->schema_json['title'] ?? $contract->form->title }}</strong></div>
                                @if($contract->form->status === 'published' && $contract->form->current_published_version_id && $contract->form->publishedVersion)
                                    <a class="admin-button admin-button-primary" href="{{ route('public-forms.show', $contract->form->slug) }}">View attached form @include('partials.admin.icon', ['name' => 'arrow'])</a>
                                @else
                                    <span class="client-contract-form-note">Not currently published</span>
                                @endif
                            @else
                                <p class="client-contract-form-note">No form currently attached.</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="admin-card client-contract-empty"><span class="client-contract-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'ticket'])</span><h3>No contract assigned yet.</h3><p>Your contract details will appear here once an agreement is assigned to your account.</p></div>
                @endforelse
            </div>
            @include('forms.partials.pagination', ['paginator' => $contracts])
        </section>
@endsection

@extends('layouts.admin')
@section('title', 'My dashboard — BITS')
@section('content')
    <div class="admin-page-heading client-dashboard-heading">
        <div><p class="admin-eyebrow">YOUR BITS ACCOUNT</p><h1>My dashboard</h1><p>Your account details and the contracts assigned to you, in one place.</p></div>
        <a class="admin-button" href="#my-contracts">View my contracts @include('partials.admin.icon', ['name' => 'arrow'])</a>
    </div>

    <div class="client-dashboard-grid">
        <section class="admin-card client-account-card" id="account-details" aria-labelledby="client-account-title">
            <div class="client-account-profile">
                <span class="client-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($account->name, 0, 2)) }}</span>
                <div><p class="admin-eyebrow">MY ACCOUNT</p><h2>{{ $account->name }}</h2><span class="admin-status {{ $account->is_active ? 'status-active' : '' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></div>
            </div>
            <div class="client-account-details">
                <h2 id="client-account-title">Account details</h2>
                <dl>
                    <div><dt>Name</dt><dd>{{ $account->name }}</dd></div>
                    <div><dt>Email address</dt><dd>{{ $account->email }}</dd></div>
                    <div><dt>Role</dt><dd>{{ $account->role?->name ?? 'Account' }}</dd></div>
                    <div><dt>Account status</dt><dd>{{ $account->is_active ? 'Active' : 'Inactive' }}</dd></div>
                    <div><dt>Member since</dt><dd>{{ $account->created_at?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt>Email verification</dt><dd>{{ $account->email_verified_at ? 'Verified' : 'Not yet verified' }}</dd></div>
                </dl>
            </div>
        </section>

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
    </div>
    <section class="client-responses-section" id="my-responses" aria-labelledby="client-responses-title">
        <div class="client-section-heading"><div><p class="admin-eyebrow">FORM RESPONSES</p><h2 id="client-responses-title">Responses linked to you</h2></div><span class="client-contract-count">{{ number_format($responses->total()) }} {{ $responses->total() === 1 ? 'response' : 'responses' }}</span></div>
        <p class="fm-muted">Responses that selected your account on forms attached to your assigned contracts.</p>
        <div class="admin-card client-responses-card">
            <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Response</th><th scope="col">Form / Contract</th><th scope="col">Received (UTC)</th><th scope="col">Details</th></tr></thead><tbody>
                @forelse($responses as $response)
                    <tr><td>#{{ $response->id }}<small class="client-response-meta">Version {{ $response->version->version_number }}</small></td><td>{{ $response->version->schema_json['title'] ?? $response->form->title }}<small class="client-response-meta">{{ $response->form->contract->name }}</small></td><td><time datetime="{{ $response->submitted_at->toIso8601String() }}">{{ $response->submitted_at->utc()->format('M j, Y H:i') }}</time></td><td><a class="fm-text-link" href="{{ route('clients.responses.show', $response) }}">View response →</a></td></tr>
                @empty
                    <tr><td colspan="4"><div class="client-response-empty"><h3>No responses linked to you yet.</h3><p>A response appears here when someone selects your account on an eligible form.</p></div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        @include('forms.partials.pagination', ['paginator' => $responses])
    </section>
@endsection

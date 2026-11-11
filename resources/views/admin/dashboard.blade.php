@extends('layouts.admin')
@section('title', 'Workspace overview — BITS')
@section('content')
    <div class="admin-page-heading premium-dashboard-heading">
        <div class="premium-dashboard-heading-copy"><p class="admin-eyebrow">YOUR WORKSPACE, AT A GLANCE</p><h1>Workspace overview</h1><p>{{ $isAdmin ? 'Live client, contract, and form totals across your workspace.' : 'Your assigned contracts and published forms.' }}</p></div>
        <div class="admin-heading-actions">@can('manage-contracts')<a class="admin-button" href="{{ route('contracts.create') }}">@include('partials.admin.icon', ['name' => 'ticket']) Add contract</a>@endcan<a class="admin-button admin-button-primary" href="{{ route('forms.create') }}">@include('partials.admin.icon', ['name' => 'plus']) Create form</a></div>
    </div>
    <section class="admin-stats dashboard-business-stats premium-dashboard-stats" aria-label="Workspace statistics">
        @if($isAdmin)
            <a class="admin-stat premium-stat premium-stat-active" href="{{ route('clients.index', ['status' => 'active']) }}"><div><span>Active clients</span><span class="admin-stat-icon tone-green">@include('partials.admin.icon', ['name' => 'users'])</span></div><strong>{{ number_format($stats['active_clients']) }}</strong><p>Agency accounts with sign-in enabled</p></a>
            <a class="admin-stat premium-stat premium-stat-inactive" href="{{ route('clients.index', ['status' => 'inactive']) }}"><div><span>Inactive clients</span><span class="admin-stat-icon tone-orange">@include('partials.admin.icon', ['name' => 'users'])</span></div><strong>{{ number_format($stats['inactive_clients']) }}</strong><p>Agency accounts with sign-in disabled</p></a>
        @endif
        @foreach([true => ['Active contracts', 'active_contracts', 'green'], false => ['Inactive contracts', 'inactive_contracts', 'orange']] as $active => [$label, $key, $tone])
            <article class="admin-stat premium-stat {{ $active ? 'premium-stat-active' : 'premium-stat-inactive' }}"><div><span>{{ $label }}</span><span class="admin-stat-icon tone-{{ $tone }}">@include('partials.admin.icon', ['name' => 'ticket'])</span></div><strong>{{ number_format($stats[$key]) }}</strong><p>@if($isAdmin)<a class="fm-text-link" href="{{ route('contracts.index', ['status' => $active ? 'active' : 'inactive']) }}">View contracts →</a>@else Assigned to your account @endif</p></article>
        @endforeach
        <article class="admin-stat premium-stat premium-stat-published"><div><span>Published forms</span><span class="admin-stat-icon tone-blue">@include('partials.admin.icon', ['name' => 'check'])</span></div><strong>{{ number_format($stats['published_forms']) }}</strong><p>{{ $isAdmin ? 'Across all form owners' : 'Owned by your account' }}</p></article>
    </section>
    <section class="dashboard-comparisons premium-dashboard-comparisons" aria-labelledby="dashboard-comparisons-title">
        <div class="dashboard-comparisons-heading"><div><p class="admin-eyebrow">STATUS BREAKDOWN</p><h2 id="dashboard-comparisons-title">Compare your workspace</h2></div><p class="premium-dashboard-live"><span aria-hidden="true"></span>Current totals · Counts and percentage share</p></div>
        <div class="dashboard-comparison-grid">
            @if($isAdmin)
                @include('partials.admin.status-chart', [
                    'chartId' => 'client-status', 'chartTitle' => 'Client status',
                    'chartCaption' => 'Agency accounts across the workspace', 'chartType' => 'donut',
                    'chartNote' => 'Active accounts have sign-in enabled.', 'emptyMessage' => 'No clients yet.',
                    'segments' => [
                        ['label' => 'Active', 'count' => $stats['active_clients'], 'tone' => 'active', 'href' => route('clients.index', ['status' => 'active'])],
                        ['label' => 'Inactive', 'count' => $stats['inactive_clients'], 'tone' => 'inactive', 'href' => route('clients.index', ['status' => 'inactive'])],
                    ],
                ])
            @endif
            @include('partials.admin.status-chart', [
                'chartId' => 'contract-status', 'chartTitle' => 'Contract status',
                'chartCaption' => $isAdmin ? 'All contracts in the workspace' : 'Contracts assigned to your account', 'chartType' => 'donut',
                'chartNote' => 'Based on status, independent of contract dates.', 'emptyMessage' => 'No contracts yet.',
                'segments' => [
                    ['label' => 'Active', 'count' => $stats['active_contracts'], 'tone' => 'active', 'href' => $isAdmin ? route('contracts.index', ['status' => 'active']) : null],
                    ['label' => 'Inactive', 'count' => $stats['inactive_contracts'], 'tone' => 'inactive', 'href' => $isAdmin ? route('contracts.index', ['status' => 'inactive']) : null],
                ],
            ])
            @include('partials.admin.status-chart', [
                'chartId' => 'form-status', 'chartTitle' => 'Form publishing status',
                'chartCaption' => $isAdmin ? 'Forms across all owners' : 'Forms owned by your account', 'chartType' => 'bar',
                'chartNote' => 'Each bar shows its share of all forms.', 'emptyMessage' => 'No forms yet.',
                'segments' => [
                    ['label' => 'Published', 'count' => $formStatusCounts['published'], 'tone' => 'published'],
                    ['label' => 'Draft', 'count' => $formStatusCounts['draft'], 'tone' => 'draft'],
                    ['label' => 'Archived', 'count' => $formStatusCounts['archived'], 'tone' => 'archived'],
                ],
            ])
        </div>
    </section>
    <div class="dashboard-business-grid premium-dashboard-records">
        <section class="admin-card premium-dashboard-record-card" aria-labelledby="dashboard-contracts-title">
            <div class="admin-card-heading"><div class="premium-record-heading"><span class="premium-record-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'ticket'])</span><div><h2 id="dashboard-contracts-title">Recent contracts</h2><p>Latest agreements and their terms</p></div></div>@can('manage-contracts')<a class="admin-text-button" href="{{ route('contracts.index') }}">View all →</a>@endcan</div>
            <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Contract</th><th scope="col">Status</th><th scope="col">Term</th></tr></thead><tbody>
                @forelse($recentContracts as $contract)
                    <tr><td>@can('manage-contracts')<a class="fm-text-link" href="{{ route('contracts.edit', $contract) }}">{{ $contract->name }}</a>@else{{ $contract->name }}@endcan</td><td><span class="admin-status {{ $contract->is_active ? 'status-active' : '' }}">{{ $contract->is_active ? 'Active' : 'Inactive' }}</span></td><td>{{ $contract->is_indefinite ? 'Indefinite' : $contract->starts_at?->format('M j, Y').' – '.$contract->ends_at?->format('M j, Y') }}</td></tr>
                @empty
                    <tr><td colspan="3" class="premium-dashboard-empty"><span class="premium-empty-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'ticket'])</span><p>No contracts yet.</p></td></tr>
                @endforelse
            </tbody></table></div>
        </section>
        <section class="admin-card premium-dashboard-record-card" aria-labelledby="dashboard-forms-title">
            <div class="admin-card-heading"><div class="premium-record-heading"><span class="premium-record-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'check'])</span><div><h2 id="dashboard-forms-title">Published forms</h2><p>Most recently updated</p></div></div><a class="admin-text-button" href="{{ route('forms.index', ['status' => 'published']) }}">My forms →</a></div>
            <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Form</th><th scope="col">Owner</th><th scope="col">Updated</th></tr></thead><tbody>
                @forelse($publishedForms as $form)
                    <tr><td>@can('update', $form)<a class="fm-text-link" href="{{ route('forms.edit', $form) }}">{{ $form->title }}</a>@else{{ $form->title }}@endcan</td><td>{{ $form->user?->name ?? 'Removed owner' }}</td><td>{{ $form->updated_at->format('M j, Y') }}</td></tr>
                @empty
                    <tr><td colspan="3" class="premium-dashboard-empty"><span class="premium-empty-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'check'])</span><p>No published forms yet.</p></td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>
@endsection

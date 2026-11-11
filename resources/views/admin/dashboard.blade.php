@extends('layouts.admin')

@section('content')
    <div class="admin-page-heading">
        <div><p class="admin-eyebrow">YOUR WORKSPACE, AT A GLANCE</p><h1 data-admin-heading>Systems overview</h1><p data-admin-description>A clearer picture of your infrastructure, team, and service delivery.</p></div>
        <div class="admin-heading-actions" data-admin-js><button class="admin-button" type="button" data-admin-export>@include('partials.admin.icon', ['name' => 'download']) Export inventory</button><button class="admin-button admin-button-primary" type="button" data-admin-add>@include('partials.admin.icon', ['name' => 'plus']) Add system</button></div>
    </div>

    <section id="overview" class="admin-panel" data-admin-panel="overview" aria-label="Systems overview">
        <div class="admin-stats">
            @foreach ([['Total systems', count($systems), 'systems', 'server', 'Across all environments', 'purple'], ['Healthy systems', 4, 'healthy', 'check', 'Running within thresholds', 'green'], ['Active alerts', 2, 'alerts', 'alert', '1 critical · 1 warning', 'orange'], ['Open tickets', 3, 'tickets', 'ticket', '1 in progress · 1 waiting', 'blue']] as [$label, $value, $stat, $icon, $hint, $tone])
                <article class="admin-stat"><div><span>{{ $label }}</span><span class="admin-stat-icon tone-{{ $tone }}">@include('partials.admin.icon', ['name' => $icon])</span></div><strong data-admin-stat="{{ $stat }}">{{ str_pad($value, 2, '0', STR_PAD_LEFT) }}</strong><p>{{ $hint }}</p></article>
            @endforeach
        </div>
        <div class="admin-overview-grid">
            <section class="admin-card admin-resource-card" aria-labelledby="resource-title">
                <div class="admin-card-heading"><div><h2 id="resource-title">Resource utilisation</h2><p>Illustrative samples · Monday–Sunday</p></div><div class="admin-segmented" aria-label="Resource chart" data-admin-js><button type="button" aria-pressed="true" data-admin-chart="cpu">CPU</button><button type="button" aria-pressed="false" data-admin-chart="memory">Memory</button></div></div>
                <div class="admin-chart-summary"><strong data-admin-chart-value>53<span>%</span></strong><span data-admin-chart-caption>Latest CPU sample</span><span class="admin-chart-period">Sample week</span></div>
                <div class="admin-bar-chart" role="img" aria-label="Sample CPU utilisation Monday to Sunday: 34, 46, 39, 57, 49, 64, 53 percent" data-admin-chart-visual>
                    <div class="admin-chart-axis" aria-hidden="true"><span>100%</span><span>50%</span><span>0%</span></div>
                    <div class="admin-chart-bars">
                        @foreach (['Mon' => 34, 'Tue' => 46, 'Wed' => 39, 'Thu' => 57, 'Fri' => 49, 'Sat' => 64, 'Sun' => 53] as $day => $value)
                            <div><div class="admin-bar-track"><span style="height: {{ $value }}%" data-admin-bar title="{{ $day }}: {{ $value }}%"></span></div><small>{{ $day }}</small></div>
                        @endforeach
                    </div>
                </div>
            </section>
            <section class="admin-card" aria-labelledby="attention-title">
                <div class="admin-card-heading"><div><h2 id="attention-title">Needs attention</h2><p>A little focus goes a long way.</p></div><span class="admin-alert-count">2</span></div>
                <div class="admin-alert-item"><span class="admin-alert-symbol critical">@include('partials.admin.icon', ['name' => 'activity'])</span><div><span class="admin-status status-critical">Critical</span><h3>Database under pressure</h3><p>Primary database is using 91% CPU and 94% memory.</p><button class="admin-text-button" type="button" data-admin-system="SYS-003" data-admin-js>Review system @include('partials.admin.icon', ['name' => 'arrow'])</button></div></div>
                <div class="admin-alert-item"><span class="admin-alert-symbol warning">@include('partials.admin.icon', ['name' => 'server'])</span><div><span class="admin-status status-warning">Warning</span><h3>Check the backup job</h3><p>The last scheduled backup completed with a warning.</p><button class="admin-text-button" type="button" data-admin-system="SYS-004" data-admin-js>Review system @include('partials.admin.icon', ['name' => 'arrow'])</button></div></div>
            </section>
        </div>
        <section class="admin-card" aria-labelledby="inventory-preview-title"><div class="admin-card-heading"><div><h2 id="inventory-preview-title">Infrastructure snapshot</h2><p>Your core systems and their latest sample readings.</p></div><a class="admin-text-button" href="#systems" data-admin-nav="systems">View all systems @include('partials.admin.icon', ['name' => 'arrow'])</a></div>@include('partials.admin.systems-table', ['listedSystems' => array_slice($systems, 0, 4), 'tableScope' => 'overview'])</section>
    </section>

    <section id="systems" class="admin-panel" data-admin-panel="systems" aria-labelledby="systems-title">
        <section class="admin-card"><div class="admin-card-heading"><div><h2 id="systems-title">System inventory</h2><p>Explore host details and resource usage across your workspace.</p></div><label class="admin-select-label" data-admin-js>Status <select data-admin-status><option value="all">All statuses</option><option>Healthy</option><option>Warning</option><option>Critical</option><option value="Unknown">Not monitored</option></select></label></div>@include('partials.admin.systems-table', ['listedSystems' => $systems, 'tableScope' => 'systems'])<div class="admin-card-footer"><span><strong data-admin-count="systems">6</strong> systems in this demo</span><span>Local preview · No monitoring connection</span></div></section>
    </section>

    <section id="tickets" class="admin-panel" data-admin-panel="tickets" aria-labelledby="tickets-title">
        <section class="admin-card"><div class="admin-card-heading"><div><h2 id="tickets-title">Service tickets</h2><p>Keep ownership and next steps in view.</p></div><label class="admin-select-label" data-admin-js>Status <select data-admin-ticket-status><option value="all">All tickets</option><option>Open</option><option>In progress</option><option>Waiting</option><option>Resolved</option></select></label></div>
            <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Ticket</th><th scope="col">Priority</th><th scope="col">Status</th><th scope="col">Assigned to</th><th scope="col">Updated</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead><tbody>
                @foreach ($tickets as $ticket)
                    <tr data-admin-row data-search="{{ strtolower(implode(' ', $ticket)) }}" data-ticket-status="{{ $ticket['status'] }}"><td><div class="admin-ticket-subject"><small>{{ $ticket['id'] }} · {{ $ticket['category'] }}</small><strong>{{ $ticket['subject'] }}</strong></div></td><td><span class="admin-priority priority-{{ strtolower($ticket['priority']) }}"><i></i>{{ $ticket['priority'] }}</span></td><td><span class="admin-status status-{{ str_replace(' ', '-', strtolower($ticket['status'])) }}">{{ $ticket['status'] }}</span></td><td>{{ $ticket['assignee'] }}</td><td class="admin-muted">{{ $ticket['updated'] }}</td><td><button class="admin-row-action" type="button" data-admin-ticket="{{ $ticket['id'] }}" data-admin-js aria-label="View ticket {{ $ticket['id'] }}">Details @include('partials.admin.icon', ['name' => 'arrow'])</button></td></tr>
                @endforeach
            </tbody></table><p class="admin-empty" data-admin-empty hidden>No tickets match your search or filter.</p></div>
        </section>
    </section>

    <section id="access" class="admin-panel" data-admin-panel="access" aria-labelledby="access-title">
        <section class="admin-card"><div class="admin-card-heading"><div><h2 id="access-title">Users & access</h2><p>Sample team members and workspace roles.</p></div><label class="admin-select-label" data-admin-js>Role <select data-admin-role><option value="all">All roles</option><option>Admin</option><option>Engineer</option><option>Viewer</option></select></label></div>
            <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Team member</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Last active</th></tr></thead><tbody>
                @foreach ($members as $member)
                    <tr data-admin-row data-search="{{ strtolower(implode(' ', $member)) }}" data-role="{{ $member['role'] }}"><td><div class="admin-system-name"><span class="admin-member-avatar">{{ $member['initials'] }}</span><div><strong>{{ $member['name'] }}</strong><small>{{ $member['email'] }}</small></div></div></td><td><span class="admin-role">{{ $member['role'] }}</span></td><td><span class="admin-status status-{{ strtolower($member['status']) }}"><i></i>{{ $member['status'] }}</span></td><td class="admin-muted">{{ $member['last_active'] }}</td></tr>
                @endforeach
            </tbody></table><p class="admin-empty" data-admin-empty hidden>No team members match your search or filter.</p></div>
            <div class="admin-card-footer"><span>5 sample members</span><span>Roles are illustrative; access controls are not connected.</span></div>
        </section>
    </section>

    <section id="activity" class="admin-panel" data-admin-panel="activity" aria-labelledby="activity-title">
        <section class="admin-card"><div class="admin-card-heading"><div><h2 id="activity-title">Activity log</h2><p>A sample timeline of changes across the workspace.</p></div><span class="admin-subtle-label">SAMPLE DAY</span></div><ol class="admin-event-list">
            @foreach ($events as $event)
                <li data-admin-row data-search="{{ strtolower(implode(' ', $event)) }}"><time>{{ $event['time'] }}</time><span class="admin-event-icon status-{{ strtolower($event['type']) }}">@include('partials.admin.icon', ['name' => $event['icon']])</span><div><h3>{{ $event['title'] }}</h3><p>{{ $event['description'] }}</p></div><span class="admin-event-type">{{ $event['type'] }}</span></li>
            @endforeach
        </ol><p class="admin-empty" data-admin-empty hidden>No events match your search.</p></section>
    </section>
@endsection

@push('dialogs')
    <dialog class="admin-dialog" id="admin-details" aria-labelledby="admin-detail-title"><div class="admin-dialog-heading"><div><p class="admin-eyebrow" data-admin-detail-kind>SYSTEM DETAILS</p><h2 id="admin-detail-title">System details</h2></div><button class="admin-icon-button" type="button" data-admin-close aria-label="Close details">@include('partials.admin.icon', ['name' => 'close'])</button></div><dl class="admin-detail-grid" data-admin-detail-fields></dl><p class="admin-dialog-note">Illustrative sample data. No live infrastructure is connected.</p></dialog>
    <dialog class="admin-dialog" id="admin-add-system" aria-labelledby="admin-add-title"><div class="admin-dialog-heading"><div><p class="admin-eyebrow">EXPAND YOUR INVENTORY</p><h2 id="admin-add-title">Add a system</h2></div><button class="admin-icon-button" type="button" data-admin-close aria-label="Close add system form">@include('partials.admin.icon', ['name' => 'close'])</button></div><p class="admin-dialog-description">Add a sample system to explore the inventory. Changes reset on refresh.</p>
        <form class="admin-add-form" data-admin-add-form>
            <label>System name<input name="name" placeholder="e.g. Application server 03" required maxlength="80" autocomplete="off"></label>
            <label>Hostname<input name="hostname" placeholder="e.g. app-03.internal" required maxlength="120" pattern="[a-zA-Z0-9._-]+" title="Use letters, numbers, dots, hyphens, or underscores." autocomplete="off"></label>
            <div class="admin-form-row"><label>Environment<select name="environment"><option>Production</option><option>Staging</option><option>Development</option></select></label><label>System type<select name="type"><option>Application</option><option>Web server</option><option>Database</option><option>Storage</option><option>Network</option></select></label></div>
            <label>Operating system<input name="os" placeholder="e.g. Ubuntu 24.04 LTS" maxlength="80" autocomplete="off"></label>
            <div class="admin-dialog-actions"><button class="admin-button" type="button" data-admin-close>Cancel</button><button class="admin-button admin-button-primary" type="submit">Add to demo inventory</button></div>
        </form>
    </dialog>
@endpush

@push('dashboard-data')
    <script type="application/json" id="admin-demo-data">@json(['systems' => $systems, 'tickets' => $tickets])</script>
@endpush

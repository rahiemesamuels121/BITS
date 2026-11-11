<div class="admin-table-scroll">
    <table class="admin-table">
        <thead><tr><th scope="col">System</th><th scope="col">Environment</th><th scope="col">Status</th><th scope="col">CPU</th><th scope="col">Memory</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody data-admin-systems="{{ $tableScope }}">
            @foreach ($listedSystems as $system)
                <tr data-admin-row data-search="{{ strtolower(implode(' ', [$system['name'], $system['hostname'], $system['environment'], $system['status']])) }}" data-status="{{ $system['status'] }}">
                    <td><div class="admin-system-name"><span class="admin-system-icon">@include('partials.admin.icon', ['name' => 'server'])</span><div><strong>{{ $system['name'] }}</strong><small>{{ $system['hostname'] }}</small></div></div></td>
                    <td><span class="admin-environment">{{ $system['environment'] }}</span></td>
                    <td><span class="admin-status status-{{ strtolower($system['status']) }}"><i></i>{{ $system['status'] }}</span></td>
                    <td><div class="admin-utilisation"><span>{{ $system['cpu'] }}%</span><i><b style="width: {{ $system['cpu'] }}%" class="{{ $system['cpu'] > 85 ? 'is-high' : '' }}"></b></i></div></td>
                    <td><div class="admin-utilisation"><span>{{ $system['memory'] }}%</span><i><b style="width: {{ $system['memory'] }}%" class="{{ $system['memory'] > 85 ? 'is-high' : '' }}"></b></i></div></td>
                    <td><button class="admin-row-action" type="button" data-admin-system="{{ $system['id'] }}" data-admin-js aria-label="View {{ $system['name'] }} details">Details @include('partials.admin.icon', ['name' => 'arrow'])</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="admin-empty" data-admin-empty hidden>No systems match your search or filter.</p>
</div>

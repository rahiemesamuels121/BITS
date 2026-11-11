@php
    $total = array_sum(array_column($segments, 'count'));
    $offset = 0;
@endphp
<figure class="admin-card dashboard-comparison" aria-labelledby="{{ $chartId }}-title">
    <figcaption class="admin-card-heading">
        <div><h2 id="{{ $chartId }}-title">{{ $chartTitle }}</h2><p>{{ $chartCaption }}</p></div>
        <span class="dashboard-chart-total">{{ number_format($total) }} total</span>
    </figcaption>
    @if($chartType === 'donut')
        <div class="dashboard-donut">
            <svg viewBox="0 0 160 160" aria-hidden="true" focusable="false">
                <circle class="dashboard-donut-track" cx="80" cy="80" r="62" />
                @foreach($segments as $segment)
                    @php($share = $total > 0 ? $segment['count'] / $total * 100 : 0)
                    @if($share > 0)
                        <circle class="dashboard-donut-segment chart-tone-{{ $segment['tone'] }}" cx="80" cy="80" r="62" pathLength="100" stroke-dasharray="{{ round($share, 4) }} {{ round(100 - $share, 4) }}" stroke-dashoffset="{{ round(-$offset, 4) }}" transform="rotate(-90 80 80)" />
                    @endif
                    @php($offset += $share)
                @endforeach
            </svg>
            <div class="dashboard-donut-center"><strong>{{ $total > 0 ? number_format($segments[0]['count'] / $total * 100, 1).'%' : '—' }}</strong><span>{{ $total > 0 ? strtolower($segments[0]['label']).' share' : 'No data yet' }}</span></div>
        </div>
    @endif
    <dl class="dashboard-chart-legend {{ $chartType === 'bar' ? 'dashboard-bar-comparison' : '' }}">
        @foreach($segments as $segment)
            @php($share = $total > 0 ? $segment['count'] / $total * 100 : 0)
            <div class="dashboard-chart-row chart-tone-{{ $segment['tone'] }}">
                <dt><span class="dashboard-chart-key" aria-hidden="true"></span>@if(!empty($segment['href']))<a href="{{ $segment['href'] }}">{{ $segment['label'] }}</a>@else{{ $segment['label'] }}@endif</dt>
                <dd><strong>{{ number_format($segment['count']) }}</strong><span>{{ number_format($share, 1) }}%</span>
                    @if($chartType === 'bar')
                        <div class="dashboard-comparison-track" aria-hidden="true"><span style="width: {{ round($share, 4) }}%"></span></div>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
    @if($chartType === 'bar')
        <div class="dashboard-chart-axis" aria-hidden="true"><span>0%</span><span>50%</span><span>100%</span></div>
    @endif
    <p class="dashboard-chart-note">{{ $total > 0 ? $chartNote : $emptyMessage }}</p>
</figure>

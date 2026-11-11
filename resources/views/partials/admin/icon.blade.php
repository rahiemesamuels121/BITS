@php
    $path = match ($name) {
        'grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'server' => 'M4 3h16v7H4zM4 14h16v7H4zM7 6.5h.01M7 17.5h.01M11 6.5h6M11 17.5h6',
        'ticket' => 'M3 5h18v4a3 3 0 0 0 0 6v4H3v-4a3 3 0 0 0 0-6V5ZM14 8v2m0 4v2',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
        'activity' => 'M3 12h4l3-8 4 16 3-8h4',
        'search' => 'M21 21l-5-5M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z',
        'plus' => 'M12 5v14M5 12h14',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'download' => 'M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'close' => 'm6 6 12 12M6 18 18 6',
        'check' => 'm5 12 4 4L19 6',
        'shield' => 'm12 3 8 3v5c0 5-8 10-8 10s-8-5-8-10V6l8-3Zm-4 9 3 3 5-6',
        'cpu' => 'M7 7h10v10H7zM9 1v3m6-3v3M9 20v3m6-3v3M1 9h3m-3 6h3M20 9h3m-3 6h3M10 10h4v4h-4z',
        'alert' => 'm12 3 10 18H2L12 3Zm0 6v5m0 3v.01',
        'back' => 'M19 12H5m6-6-6 6 6 6',
        'logout' => 'M9 5H4v14h5M14 8l4 4-4 4M9 12h12',
        default => 'M12 3v18M3 12h18',
    };
@endphp
<svg class="admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}" /></svg>

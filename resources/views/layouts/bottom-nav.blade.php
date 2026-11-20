{{--
    Phone navigation: a frosted-glass bar with six equal tabs. The current tab gets a tinted rounded square behind its
    icon and an accent-coloured label.
--}}
@php
    $tabs = [
        'catalog' => ['Home', ['catalog', 'catalog.all', 'categories.show'], 'M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-8.5Z'],
        'rental' => ['Rental', ['rental'], 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM12 7v5l3 2'],
        'joki' => ['Joki ML', ['joki'], 'M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7ZM7.5 10v5M5 12.5h5M16 11h.01M18 14h.01'],
        'reports' => ['Laporan', ['reports', 'reports.show'], 'M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4M12 17h.01'],
        'about' => ['Tentang', ['about'], 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM12 16v-4M12 8h.01'],
        'profile' => ['Akun', ['profile'], 'M20 21v-1a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
    ];
@endphp
<nav class="bnav fixed inset-x-3 bottom-3 z-40 mx-auto grid max-w-md grid-cols-6 rounded-[1.4rem] px-1 py-1.5 xl:hidden" aria-label="Navigasi bawah">
    @foreach ($tabs as $route => [$label, $activeRoutes, $icon])
        @php($isActive = request()->routeIs(...$activeRoutes))
        <a href="{{ route($route) }}" class="bnav-tab {{ $isActive ? 'is-active' : '' }}" @if ($isActive) aria-current="page" @endif>
            <span class="bnav-icon" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="{{ $icon }}" /></svg>
            </span>
            <span class="max-w-full truncate">{{ $label }}</span>
        </a>
    @endforeach
</nav>

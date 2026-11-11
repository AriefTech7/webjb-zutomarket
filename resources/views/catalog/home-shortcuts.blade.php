@php
    // Line icons picked from the category name; anything unrecognised gets a gamepad.
    $categoryIcon = function (string $name): string {
        $name = strtoupper($name);

        return match (true) {
            str_contains($name, 'FF') || str_contains($name, 'FREE FIRE') => 'M12 22c4 0 7-2.7 7-6.5 0-3-2-5.5-3.5-7-.3 2-1.3 3-2.5 3.5.5-3-1-6.5-4-9 0 3-1.5 5-3 6.5S5 13 5 15.5C5 19.3 8 22 12 22Z',
            str_contains($name, 'ML') || str_contains($name, 'MOBILE LEGEND') => 'M14.5 17.5 3 6V3h3l11.5 11.5M13 19l6-6M16 16l4 4M19 21l2-2',
            str_contains($name, 'FOOTBALL') || str_contains($name, 'BOLA') => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 7l4 3-1.5 4.5h-5L8 10l4-3ZM12 2v5M16 10l5-1.5M14.5 14.5 17 19M9.5 14.5 7 19M8 10 3 8.5',
            str_contains($name, 'VALORANT') => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 2v4M12 18v4M2 12h4M18 12h4M12 12h.01',
            str_contains($name, 'PUBG') => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM12 12h.01',
            str_contains($name, 'GENSHIN') => 'm12 3 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9L12 3Z',
            str_contains($name, 'TIKTOK') => 'M9 18V5l12-2v13M9 18a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM21 16a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
            default => 'M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7ZM7.5 10v5M5 12.5h5',
        };
    };
    $shortcuts = array_values(array_filter([
        ['Produk', route('catalog.all'), 'M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7ZM7.5 10v5M5 12.5h5'],
        $hasFlashSale ? ['Flash Sale', route('flash-sale'), 'M13 2 4 14h7l-1 8 9-12h-7l1-8Z'] : null,
        ['Rental Akun', route('rental'), 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 7v5l3 2'],
        ['Joki ML', route('joki'), 'M12 2 4 5v7c0 5 8 10 8 10s8-5 8-10V5l-8-3Z'],
    ]));
@endphp
<nav class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 [scrollbar-width:none] md:mx-0 md:flex-wrap md:px-0" aria-label="Pintasan">
    @foreach ($shortcuts as [$label, $url, $icon])
        <a href="{{ $url }}" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-[#a9c3ff]/15 bg-[#101833]/85 px-3.5 py-2 text-xs font-bold text-slate-100 transition hover:border-lime-300/50">
            <svg class="h-4 w-4 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg>{{ $label }}
        </a>
    @endforeach
</nav>
@if ($categories->isNotEmpty())
    <div class="mt-3 grid grid-cols-2 gap-2 md:mt-4 md:gap-3 lg:grid-cols-3" aria-label="Kategori game">
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}#katalog"
                class="flex min-h-11 min-w-0 items-center gap-2.5 rounded-xl border border-[#a9c3ff]/15 bg-[#101833]/85 px-3 py-2 transition hover:-translate-y-0.5 hover:border-lime-300/40 md:min-h-16 md:gap-3 md:rounded-2xl md:px-5 {{ $category->accounts_count ? '' : 'opacity-60' }}">
                <svg class="h-4 w-4 shrink-0 text-lime-300 md:h-5 md:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $categoryIcon($category->name) }}" /></svg>
                <span class="min-w-0">
                    <span class="line-clamp-2 break-words text-[10px] font-black uppercase leading-tight md:text-sm">{{ $category->name }}</span>
                    <span class="block text-[9px] text-slate-400 md:text-[11px]">{{ $category->accounts_count }} akun tersedia</span>
                </span>
            </a>
        @endforeach
    </div>
@endif

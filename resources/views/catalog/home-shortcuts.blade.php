@php
    $gradients = ['from-sky-400 to-blue-600', 'from-amber-300 to-rose-500', 'from-fuchsia-400 to-violet-600', 'from-emerald-300 to-teal-600', 'from-rose-400 to-pink-600', 'from-indigo-400 to-blue-700'];
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
    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-3" aria-label="Kategori game">
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}#katalog"
                class="flex min-w-0 items-center gap-2.5 rounded-2xl border border-[#a9c3ff]/15 bg-linear-to-br from-[#16204a] to-[#0d1430] p-2.5 transition hover:-translate-y-0.5 hover:border-lime-300/40 md:gap-3 md:p-4 {{ $category->accounts_count ? '' : 'opacity-60' }}">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-linear-to-br {{ $gradients[$loop->index % count($gradients)] }} text-white shadow-lg md:size-12">
                    <svg class="h-5 w-5 md:h-6 md:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7ZM7.5 10v5M5 12.5h5" /></svg>
                </span>
                <span class="min-w-0">
                    <span class="line-clamp-2 break-words text-xs font-black uppercase leading-tight md:text-base">{{ $category->name }}</span>
                    <span class="block text-[11px] text-slate-400">{{ $category->accounts_count }} akun tersedia</span>
                </span>
            </a>
        @endforeach
    </div>
@endif

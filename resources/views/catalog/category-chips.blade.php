<nav class="-mx-5 mb-5 flex gap-2 overflow-x-auto px-5 pb-1 [scrollbar-width:none] md:mx-0 md:mb-7 md:flex-wrap md:overflow-visible md:px-0 md:pb-0"
    aria-label="Kategori game" data-chip-row><a
        href="{{ route('catalog.all', request()->except('category', 'page')) }}#katalog"
        class="shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-medium md:px-4 md:py-2 md:text-xs {{ !$selected ? 'border-lime-300 bg-lime-300/10 text-lime-300' : 'border-white/10 text-slate-400' }}"
        @if (!$selected) aria-current="page" @endif>Semua game</a>
    @foreach ($categories as $category)
        <a href="{{ route('categories.show', ['category' => $category->slug, ...request()->except('category', 'page')]) }}#katalog"
            class="shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-medium transition hover:border-lime-300/50 md:px-4 md:py-2 md:text-xs {{ $selected === $category->slug ? 'border-lime-300 bg-lime-300/10 text-lime-300' : 'border-white/10 text-slate-400' }} {{ $category->accounts_count || $selected === $category->slug ? '' : 'opacity-60' }}"
            @if ($selected === $category->slug) aria-current="page" @endif>{{ $category->name }}<span
                class="ml-1.5 rounded-full bg-white/10 px-1.5 py-px text-[10px] font-semibold">{{ $category->accounts_count }}</span></a>
    @endforeach
</nav>

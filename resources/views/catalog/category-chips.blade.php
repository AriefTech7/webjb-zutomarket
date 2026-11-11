@php
    $chipBase = 'shrink-0 whitespace-nowrap rounded-full border px-3.5 py-2 text-xs font-bold transition md:px-4';
    $chipOn = 'border-lime-300 bg-lime-300 text-slate-950 shadow-lg shadow-lime-300/20';
    $chipOff = 'border-[#a9c3ff]/15 bg-[#101833]/85 text-slate-200 hover:border-lime-300/50';
    $status ??= null;
    $keep = request()->except('category', 'page', 'status', 'available');
    $isAll = ! $selected && $status !== 'sold';
@endphp
<nav class="-mx-5 mb-5 flex gap-2 overflow-x-auto px-5 pb-1 [scrollbar-width:none] md:mx-0 md:flex-wrap md:overflow-visible md:px-0 md:pb-0"
    aria-label="Kategori game" data-chip-row>
    <a href="{{ route('catalog.all', $keep) }}#katalog" class="{{ $chipBase }} {{ $isAll ? $chipOn : $chipOff }}" @if ($isAll) aria-current="page" @endif>Semua</a>
    @foreach ($categories as $category)
        @php($isOn = $selected === $category->slug && $status !== 'sold')
        <a href="{{ route('categories.show', ['category' => $category->slug, ...$keep]) }}#katalog"
            class="{{ $chipBase }} {{ $isOn ? $chipOn : $chipOff }} {{ $category->accounts_count || $isOn ? '' : 'opacity-60' }}"
            @if ($isOn) aria-current="page" @endif>{{ $category->name }}</a>
    @endforeach
    <a href="{{ route('catalog.all', [...$keep, 'status' => 'sold']) }}#katalog" class="{{ $chipBase }} {{ $status === 'sold' && ! $selected ? $chipOn : $chipOff }}"
        @if ($status === 'sold' && ! $selected) aria-current="page" @endif>
        <svg class="mr-1 inline h-3.5 w-3.5 align-[-2px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 22c4 0 7-2.7 7-6.5 0-3-2-5.5-3.5-7-.3 2-1.3 3-2.5 3.5.5-3-1-6.5-4-9 0 3-1.5 5-3 6.5S5 13 5 15.5C5 19.3 8 22 12 22Z" /></svg>Produk Terjual</a>
</nav>

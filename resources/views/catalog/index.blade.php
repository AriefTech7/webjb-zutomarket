@extends('layouts.app')
@section('title', 'Katalog akun')
@section('content')
    @php($hasFilter = filled($status) || request()->filled('sort'))
    <div class="shell">
        <section id="katalog" class="scroll-mt-24 pt-6 md:pt-8">
            <nav class="muted text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Home</a> / <span class="text-slate-300">Katalog</span></nav>
            <h1 class="mt-3 mb-5 flex items-center gap-3 text-3xl font-black md:text-4xl">
                <svg class="h-7 w-7 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6ZM3 6h18M16 10a4 4 0 0 1-8 0" /></svg>Katalog
            </h1>
            @include('catalog.category-chips', ['categories' => $categories, 'selected' => $selected, 'status' => $status])
            @if (request()->filled('q'))
                <p class="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-300">Hasil pencarian <strong class="text-white">"{{ request('q') }}"</strong>
                    <a class="text-xs text-lime-300 hover:underline" href="{{ url()->current().'?'.http_build_query(request()->except('q', 'page')) }}#katalog">Hapus pencarian ×</a></p>
            @endif
            <details class="mb-5" @if ($hasFilter) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                    <span class="text-sm font-bold text-slate-300">{{ $accounts->total() }} produk</span>
                    <span class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-[#a9c3ff]/15 bg-[#101833]/85 px-4 text-sm font-bold text-slate-200 transition hover:border-lime-300/50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0M14 4v4M8 10v4M16 16v4" /></svg>Filter
                    </span>
                </summary>
                <form method="GET" action="{{ url()->current() }}#katalog" class="panel mt-3 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                    @if (request()->filled('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif
                    <div><label class="label text-xs uppercase tracking-wider text-slate-400" for="filter-status">Status</label>
                        <select id="filter-status" class="field" name="status" data-submit-on-change>
                            <option value="">Semua status</option>
                            <option value="available" @selected($status === 'available')>Tersedia</option>
                            <option value="sold" @selected($status === 'sold')>Terjual</option>
                        </select></div>
                    <div><label class="label text-xs uppercase tracking-wider text-slate-400" for="filter-sort">Urutkan</label>
                        <select id="filter-sort" class="field" name="sort" data-submit-on-change>
                            <option value="newest">Terbaru</option>
                            <option value="price_low" @selected(request('sort') === 'price_low')>Harga terendah</option>
                            <option value="price_high" @selected(request('sort') === 'price_high')>Harga tertinggi</option>
                        </select></div>
                    @if ($hasFilter)
                        <a class="btn-secondary sm:col-span-2 lg:col-span-1" href="{{ url()->current().(request()->filled('q') ? '?q='.urlencode(request('q')) : '') }}#katalog">Reset filter</a>
                    @endif
                </form>
            </details>
            <div class="grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($accounts as $account)
                    @include($account->is_rental ? 'catalog.rental-card' : 'catalog.account-card', ['account' => $account])
                @empty
                    <div class="panel col-span-full p-14 text-center">
                        <p class="text-4xl text-lime-300">⌕</p>
                        <h3 class="mt-4 text-xl font-bold">Belum ada akun yang cocok</h3>
                        <p class="muted mt-2">Coba kata kunci lain atau pilih kategori berbeda.</p><a
                            class="btn-secondary mt-5" href="{{ route('catalog.all') }}">Lihat semua akun</a>
                    </div>
                @endforelse
            </div>
            <div class="mt-8">{{ $accounts->links() }}</div>
        </section>
        <section class="mt-12">
            <div class="panel flex flex-col justify-between gap-5 p-7 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-bold">Ketemu akun yang cocok?</h2>
                    <p class="muted mt-1">Buka detail akun dan diskusikan transaksi langsung dengan admin.</p>
                </div><a href="{{ route('how') }}" class="btn-secondary shrink-0">Pelajari cara transaksi ↗</a>
            </div>
        </section>
    </div>
@endsection

@extends('layouts.app')
@section('content')
    <div class="shell">
        <section class="store-hero my-8 overflow-hidden rounded-3xl border">
            <h1 class="sr-only">Jual beli akun game di ZutoMarket</h1>
            <img class="w-full" src="{{ asset('images/zuto-banner.webp') }}"
                alt="Solusi praktis untuk kebutuhan jual beli akun, hanya di zutomarket.id" width="1280" height="430"
                fetchpriority="high">
        </section>
        @if ($flashSaleCount)
            <a class="mb-8 flex items-center justify-between gap-4 rounded-2xl border border-amber-300/40 bg-linear-to-r from-amber-300/20 to-rose-400/10 p-4 transition hover:border-amber-300"
                href="{{ route('flash-sale') }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-300 text-slate-950"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" /></svg></span>
                    <span class="min-w-0">
                        <span class="block font-black uppercase tracking-wide text-amber-300">Flash Sale</span>
                        <span class="muted block text-xs">{{ $flashSaleCount }} akun · berakhir dalam <span
                                data-countdown="{{ $nextFlashSale->flash_sale_ends_at->toIso8601ZuluString() }}">{{ $nextFlashSale->flashSaleCountdown() }}</span></span>
                    </span>
                </span>
                <span class="shrink-0 text-sm font-bold text-amber-300">Lihat →</span>
            </a>
        @endif
        @if ($rentalCount)
            <a class="mb-8 flex items-center justify-between gap-4 rounded-2xl border border-lime-300/30 bg-linear-to-r from-lime-300/15 to-violet-400/10 p-4 transition hover:border-lime-300"
                href="{{ route('rental') }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-lime-300 text-slate-950"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 6v6l4 2" /></svg></span>
                    <span class="min-w-0">
                        <span class="block font-black uppercase tracking-wide text-lime-300">Rental Akun</span>
                        <span class="muted block text-xs">{{ $rentalCount }} akun · {{ $rentalReadyCount }} siap disewa</span>
                    </span>
                </span>
                <span class="shrink-0 text-sm font-bold text-lime-300">Lihat →</span>
            </a>
        @endif
        <section id="katalog" class="scroll-mt-6">
            <div class="mb-6 hidden flex-wrap items-end justify-between gap-4 md:flex">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight md:text-3xl">Jelajahi katalog<span
                            class="text-lime-300">.</span></h2>
                </div><span class="muted">{{ $accounts->total() }} akun ditemukan</span>
            </div>
            <form action="{{ route('catalog') }}#katalog" method="GET" class="panel mb-5 hidden p-4 md:block">
                <div class="grid gap-3 md:grid-cols-[1fr_190px_180px_auto]">
                    <div><label class="sr-only" for="search">Cari akun game</label><input id="search" class="field"
                            type="search" name="q" value="{{ request('q') }}" maxlength="100"
                            placeholder="Cari game, rank, hero, skin, atau server..."></div>
                    <div><label class="sr-only" for="category">Kategori game</label><select class="field" id="category"
                            name="category">
                            <option value="">Semua game</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected($selected === $category->slug)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="sr-only" for="sort">Urutkan</label><select class="field" id="sort"
                            name="sort">
                            <option value="newest">Paling baru</option>
                            <option value="price_low" @selected(request('sort') === 'price_low')>Harga terendah</option>
                            <option value="price_high" @selected(request('sort') === 'price_high')>Harga tertinggi</option>
                        </select></div><button class="btn">Cari akun ↗</button>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><label
                        class="flex items-center gap-2 text-xs text-slate-400"><input type="checkbox"
                            class="accent-lime-300" name="available" value="1" data-submit-on-change @checked(request('available'))> Hanya akun
                        tersedia</label>
                    @if (request()->query() || $selected)
                        <a class="text-xs text-lime-300" href="{{ route('catalog') }}#katalog">Reset pencarian ×</a>
                    @endif
                </div>
            </form>
            <div class="mb-7 flex flex-wrap gap-2"><a
                    href="{{ route('catalog', request()->except('category', 'page')) }}#katalog"
                    class="rounded-full border px-4 py-2 text-xs {{ !$selected ? 'border-lime-300 bg-lime-300/10 text-lime-300' : 'border-white/10 text-slate-400' }}">Semua
                    game</a>
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', ['category' => $category->slug, ...request()->except('category', 'page')]) }}#katalog"
                        class="rounded-full border px-4 py-2 text-xs transition hover:border-lime-300/50 {{ $selected === $category->slug ? 'border-lime-300 bg-lime-300/10 text-lime-300' : 'border-white/10 text-slate-400' }}">{{ $category->name }}
                        <span class="ml-1 opacity-50">{{ $category->accounts_count }}</span></a>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($accounts as $account)
                    @include('catalog.account-card', ['account' => $account])
                @empty
                    <div class="panel col-span-full p-14 text-center">
                        <p class="text-4xl text-lime-300">⌕</p>
                        <h3 class="mt-4 text-xl font-bold">Belum ada akun yang cocok</h3>
                        <p class="muted mt-2">Coba kata kunci lain atau pilih kategori berbeda.</p><a
                            class="btn-secondary mt-5" href="{{ route('catalog') }}">Lihat semua akun</a>
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

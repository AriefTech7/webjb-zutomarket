@extends('layouts.app')
@section('content')
    <div class="shell">
        <section class="store-hero my-8 overflow-hidden rounded-3xl border">
            <h1 class="sr-only">Jual beli akun game di ZutoMarket</h1>
            <img class="w-full" src="{{ asset('images/zuto-banner.webp') }}"
                alt="Solusi praktis untuk kebutuhan jual beli akun, hanya di zutomarket.id" width="1280" height="430"
                fetchpriority="high">
        </section>
        <section id="katalog" class="scroll-mt-6">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight md:text-3xl">Jelajahi katalog<span
                            class="text-lime-300">.</span></h2>
                </div><span class="muted">{{ $accounts->total() }} akun ditemukan</span>
            </div>
            <form action="{{ route('catalog') }}#katalog" method="GET" class="panel mb-5 p-4">
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
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
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

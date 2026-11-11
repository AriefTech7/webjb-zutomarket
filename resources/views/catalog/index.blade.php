@extends('layouts.app')
@section('title', 'Katalog akun')
@section('content')
    <div class="shell">
        <h1 class="sr-only">Katalog akun game ZutoMarket</h1>
        <section id="katalog" class="scroll-mt-24 pt-8">
            @include('catalog.search-panel', ['categories' => $categories, 'selected' => $selected, 'total' => $accounts->total()])
            @include('catalog.category-chips', ['categories' => $categories, 'selected' => $selected])
            <div class="grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($accounts as $account)
                    @include('catalog.account-card', ['account' => $account])
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

@extends('layouts.app')
@section('content')
    <div class="shell">
        <section class="store-hero my-8 overflow-hidden rounded-3xl border">
            <h1 class="sr-only">Jual beli akun game di ZutoMarket</h1>
            <img class="w-full" src="{{ asset('images/zuto-banner.webp') }}"
                alt="Solusi praktis untuk kebutuhan jual beli akun, hanya di zutomarket.id" width="1280" height="430"
                fetchpriority="high">
        </section>
        @include('catalog.home-shortcuts', ['categories' => $categories->sortByDesc('accounts_count')->values(), 'hasFlashSale' => $flashSale->isNotEmpty()])
        <div class="mt-8 hidden md:block">
            @include('catalog.search-panel', ['categories' => $categories, 'selected' => '', 'total' => $catalogTotal])
        </div>

        <section class="mt-6" aria-labelledby="home-available">
            @include('catalog.section-heading', ['id' => 'home-available', 'title' => 'Produk Tersedia', 'subtitle' => e($availableCount.' akun siap dibeli'), 'url' => route('catalog.all', ['available' => 1]).'#katalog'])
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 xl:grid-cols-4">
                @forelse ($available as $account)
                    @include('catalog.account-card', ['account' => $account])
                @empty
                    <p class="panel col-span-full p-8 text-center text-sm text-slate-400">Belum ada akun yang tersedia.</p>
                @endforelse
            </div>
        </section>

        @if ($flashSale->isNotEmpty())
            <section class="mt-12" aria-labelledby="home-flash">
                @include('catalog.section-heading', [
                    'id' => 'home-flash', 'title' => 'Flash Sale ⚡', 'titleClass' => 'text-amber-300', 'linkClass' => 'text-amber-300 hover:text-amber-200',
                    'subtitle' => e($flashSaleCount.' akun · berakhir dalam ').'<span data-countdown="'.e($flashSale->first()->flash_sale_ends_at->toIso8601ZuluString()).'">'.e($flashSale->first()->flashSaleCountdown()).'</span>',
                    'url' => route('flash-sale'),
                ])
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                    @foreach ($flashSale as $account)
                        @include('catalog.account-card', ['account' => $account])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($rentals->isNotEmpty())
            <section class="mt-12" aria-labelledby="home-rental">
                @include('catalog.section-heading', ['id' => 'home-rental', 'title' => 'Rental Akun', 'subtitle' => 'Sewa akun dengan paket durasi'])
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                    @foreach ($rentals as $account)
                        @include('catalog.rental-card', ['account' => $account])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($sold->isNotEmpty())
            <section class="mt-12" aria-labelledby="home-sold">
                @include('catalog.section-heading', ['id' => 'home-sold', 'title' => 'Produk Terjual', 'subtitle' => 'Akun yang baru saja laku'])
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                    @foreach ($sold as $account)
                        @include('catalog.account-card', ['account' => $account])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

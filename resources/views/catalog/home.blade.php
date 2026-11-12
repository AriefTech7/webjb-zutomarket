@extends('layouts.app')
@section('content')
    <div class="shell">
        <section class="store-hero my-8 overflow-hidden rounded-3xl border">
            <h1 class="sr-only">Jual beli akun game di ZutoMarket</h1>
            @php($banner = \App\Http\Controllers\AdminBannerController::current())
            @if ($banner['link'])<a href="{{ $banner['link'] }}" class="block">@endif
            <img class="h-auto w-full" src="{{ $banner['url'] }}"
                alt="Solusi praktis untuk kebutuhan jual beli akun, hanya di zutomarket.id" width="{{ $banner['width'] }}" height="{{ $banner['height'] }}"
                fetchpriority="high">
            @if ($banner['link'])</a>@endif
        </section>
        @include('catalog.home-shortcuts', ['categories' => $categories->sortByDesc('accounts_count')->values(), 'hasFlashSale' => $flashSale->isNotEmpty()])
        @if ($infos->isNotEmpty())
            <section class="mt-6" aria-labelledby="home-info" data-carousel>
                <div class="panel flex items-center gap-3 px-4 py-3 md:px-5 md:py-4">
                    <svg class="h-5 w-5 shrink-0 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM9 12l2 2 4-4" /></svg>
                    <h2 id="home-info" class="text-base font-black md:text-lg">Info Terbaru</h2>
                </div>
                <div class="relative mt-3">
                    <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth pb-1 [scrollbar-width:none]" data-carousel-track>
                        @foreach ($infos as $info)
                            <a class="w-36 shrink-0 snap-start overflow-hidden rounded-2xl border border-[#a9c3ff]/15 bg-[#101833] transition hover:-translate-y-0.5 hover:border-lime-300/40 md:w-52"
                                href="{{ $info->targetUrl() }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $info->title }}">
                                <img class="aspect-[3/4] w-full object-cover" src="{{ $info->imageUrl() }}" alt="{{ $info->title }}" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                    <button type="button" class="absolute left-2 top-1/2 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-[#070b1c]/80 text-white backdrop-blur md:flex" data-carousel-prev aria-label="Info sebelumnya">‹</button>
                    <button type="button" class="absolute right-2 top-1/2 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-[#070b1c]/80 text-white backdrop-blur md:flex" data-carousel-next aria-label="Info berikutnya">›</button>
                </div>
            </section>
        @endif
        <div class="mt-8 hidden md:block">
            @include('catalog.search-panel', ['categories' => $categories, 'selected' => '', 'total' => $catalogTotal])
        </div>

        @if ($flashSale->isNotEmpty())
            <section class="mt-6" aria-labelledby="home-flash">
                @include('catalog.section-heading', [
                    'id' => 'home-flash', 'title' => 'Flash Sale ⚡', 'titleClass' => 'text-amber-300', 'linkClass' => 'text-amber-300 hover:text-amber-200',
                    'subtitle' => 'Berakhir dalam <span data-countdown="'.e($flashSale->first()->flash_sale_ends_at->toIso8601ZuluString()).'">'.e($flashSale->first()->flashSaleCountdown()).'</span>',
                    'url' => route('flash-sale'),
                ])
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                    @foreach ($flashSale as $account)
                        @include('catalog.account-card', ['account' => $account])
                    @endforeach
                </div>
            </section>
        @endif

        <section class="{{ $flashSale->isNotEmpty() ? 'mt-12' : 'mt-6' }}" aria-labelledby="home-available">
            @include('catalog.section-heading', ['id' => 'home-available', 'title' => 'Produk Terbaru', 'url' => route('catalog.all', ['available' => 1]).'#katalog'])
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 xl:grid-cols-4">
                @forelse ($available as $account)
                    @include('catalog.account-card', ['account' => $account])
                @empty
                    <p class="panel col-span-full p-8 text-center text-sm text-slate-400">Belum ada akun yang tersedia.</p>
                @endforelse
            </div>
        </section>

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

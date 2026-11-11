<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        try {
            document.documentElement.dataset.theme = localStorage.getItem('zutomarket-theme') === 'light' ? 'light' : 'dark';
        } catch {
            document.documentElement.dataset.theme = 'dark';
        }
    </script>
    <title>@yield('title', 'ZutoMarket') · {{ \App\Models\Setting::valueFor('store_name', 'SHOPGAME ZUTO') }}</title>
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/icons/favicon-48.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/icons/icon-192.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ZutoMarket">
    <meta property="og:title" content="@yield('title', 'ZutoMarket') · {{ \App\Models\Setting::valueFor('store_name', 'SHOPGAME ZUTO') }}">
    <meta property="og:description" content="@yield('description', 'Jelajahi katalog akun game, pilih spesifikasi favoritmu, dan hubungi admin langsung melalui WhatsApp.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/zuto-og.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Logo ZutoMarket.id">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#070b1c">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="ZutoMarket">
    <meta name="description" content="@yield('description', 'Jelajahi katalog akun game, pilih spesifikasi favoritmu, dan hubungi admin langsung melalui WhatsApp.')">
    @if (request()->is('admin*'))
        <meta name="robots" content="noindex,nofollow">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php($buyer = auth()->user()?->is_admin === false ? auth()->user() : null)
<body class="min-h-screen flex flex-col {{ request()->is('admin*') ? '' : 'pb-24 xl:pb-0' }}"
    @if ($buyer) data-account-profile="{{ json_encode(['name' => $buyer->name, 'phone' => (string) $buyer->whatsapp]) }}" data-account-wishlist="{{ route('account.wishlist') }}"
        data-account-wishlist-ids="{{ json_encode($buyer->wishlistIds()) }}" @if (session('wishlist_merge')) data-account-wishlist-merge="1" @endif @endif>
    @php($storeName = \App\Models\Setting::valueFor('store_name', 'SHOPGAME ZUTO'))
    <header
        class="sticky top-0 z-40 border-b border-[#a9c3ff]/15 bg-[#070b1c]/90 shadow-lg shadow-[#020617]/10 backdrop-blur-xl">
        <div class="shell flex h-16 items-center gap-3 md:h-20 md:gap-4">
            <a href="{{ route('catalog') }}" class="flex shrink-0 items-center gap-3 {{ request()->is('admin*') ? 'mr-auto' : '' }}"
                aria-label="{{ $storeName }}"><span class="logo-shine" style="--logo-url: url('{{ asset('images/zuto-wordmark.webp') }}')"><img src="{{ asset('images/zuto-wordmark.webp') }}"
                    alt="{{ $storeName }}" width="1199" height="422" class="brand-wordmark w-24 md:w-40"></span><span
                    class="sr-only">{{ $storeName }}</span></a>
            @unless (request()->is('admin*'))
            <form action="{{ route('catalog.all') }}#katalog" method="GET" role="search" class="relative min-w-0 flex-1 md:max-w-md">
                <label class="sr-only" for="header-search">Cari produk</label>
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                <input id="header-search" type="search" name="q" value="{{ request()->routeIs('catalog.all', 'categories.show') ? request('q') : '' }}" maxlength="100"
                    placeholder="Cari produk..." class="h-10 w-full rounded-full border border-lime-300/30 bg-[#0b1230] pl-10 pr-4 text-sm text-white placeholder:text-slate-400 transition focus:border-lime-300/70 md:h-11">
            </form>
            <nav class="ml-auto hidden items-center gap-1 rounded-full border border-[#a9c3ff]/15 bg-[#0b1230] p-1 xl:flex" aria-label="Navigasi utama">
                @foreach ([['catalog', 'Home', ['catalog', 'catalog.all', 'categories.show']], ['rental', 'Rental Akun', ['rental']], ['joki', 'Joki ML', ['joki']], ['about', 'Tentang', ['about']], ['profile', 'Akun', ['profile', 'feedback']]] as [$navRoute, $navLabel, $navActive])
                    @php($isNavActive = request()->routeIs(...$navActive))
                    <a href="{{ route($navRoute) }}" @if ($isNavActive) aria-current="page" @endif
                        class="rounded-full px-4 py-2 text-sm font-bold transition {{ $isNavActive ? 'bg-lime-300 text-slate-950 shadow-lg shadow-lime-300/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">{{ $navLabel }}</a>
                @endforeach
            </nav>
            @endunless
            <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Ganti ke mode terang">
                <svg class="theme-icon-sun h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></svg>
                <svg class="theme-icon-moon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" /></svg>
            </button>
        </div>
    </header>
    <main class="flex-1">
        <div data-wishlist-feedback role="status" aria-live="polite"
            class="shell hidden mt-5 rounded-xl border border-rose-300/20 bg-rose-300/5 p-4 text-sm text-slate-200"></div>
        @if (session('success'))
            <div role="status" class="shell mt-5">
                <div class="rounded-xl border border-lime-300/30 bg-lime-300/10 p-4 text-sm text-lime-200">
                    {{ session('success') }}</div>
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="shell mt-5">
                <ul class="rounded-xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-200">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    @php($footerWhatsapp = preg_replace('/\D+/', '', \App\Models\Setting::valueFor('whatsapp_number')))
    @php($footerGames = request()->is('admin*') ? collect() : \App\Models\Category::orderBy('name')->limit(10)->get(['name', 'slug']))
    <footer class="mt-16 border-t border-[#a9c3ff]/15 bg-[#050817]/60 pt-12 pb-8">
        <div class="shell grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_0.8fr_1.1fr]">
            <div>
                <a href="{{ route('catalog') }}" class="inline-flex items-center gap-3">
                    <img class="size-11 rounded-xl" src="{{ asset('images/icons/icon-192.png') }}" alt="" width="44" height="44">
                    <span class="leading-none"><span class="block text-xl font-black tracking-tight text-white">ZUTO</span><span class="text-[11px] font-black tracking-[0.3em] text-lime-300">MARKET</span></span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-7 text-slate-400">Tempat jual beli akun game, rental akun, dan joki rank Mobile Legends, dengan transaksi langsung melalui WhatsApp admin.</p>
                @if ($footerWhatsapp)
                    <a class="btn mt-5 rounded-full" href="https://wa.me/{{ $footerWhatsapp }}" target="_blank" rel="noopener noreferrer">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" /></svg>WhatsApp Admin</a>
                @endif
            </div>
            @if ($footerGames->isNotEmpty())
                <div>
                    <h2 class="flex items-center gap-2 font-bold text-white"><svg class="h-5 w-5 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7ZM7.5 10v5M5 12.5h5" /></svg>Game Tersedia</h2>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-400">
                        @foreach ($footerGames as $footerGame)
                            <li><a class="transition hover:text-lime-300" href="{{ route('categories.show', $footerGame->slug) }}">{{ $footerGame->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div>
                <h2 class="font-bold text-white">Menu</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-400">
                    @foreach ([['catalog.all', 'Produk'], ['rental', 'Rental'], ['joki', 'Joki ML'], ['about', 'Tentang'], ['how', 'Cara Transaksi'], ['feedback', 'Kritik & Saran']] as [$footerRoute, $footerLabel])
                        <li><a class="transition hover:text-lime-300" href="{{ route($footerRoute) }}">{{ $footerLabel }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="font-bold text-white">Sinyal Trust</h2>
                <ul class="mt-4 space-y-3 text-sm text-slate-400">
                    @foreach ([['Transaksi aman dan data akun jelas', 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM9 12l2 2 4-4'], ['Proses cepat setelah pembayaran', 'M13 2 4 14h7l-1 8 9-12h-7l1-8Z'], ['Transaksi langsung dengan admin via WhatsApp', 'M3 14v-2a9 9 0 0 1 18 0v2M3 14a2 2 0 0 0 2 2h1v-5H5a2 2 0 0 0-2 2v1ZM21 14a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2v1ZM18 16v1a3 3 0 0 1-3 3h-3']] as [$trustText, $trustIcon])
                        <li class="flex items-center gap-3"><svg class="h-5 w-5 shrink-0 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $trustIcon }}" /></svg>{{ $trustText }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="shell mt-10">
            <div class="flex flex-col gap-4 border-t border-white/10 pt-6 text-xs text-slate-400 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1">
                    <p>© {{ date('Y') }} ZutoMarket. Platform jual beli akun game.</p>
                    <p class="font-bold text-lime-300">Created By OzinVoid</p>
                </div>
                <nav class="flex flex-wrap gap-x-4 gap-y-2" aria-label="Tautan footer">
                    @foreach ([['catalog.all', 'Produk'], ['rental', 'Rental'], ['joki', 'Joki ML'], ['about', 'Tentang'], ['privacy', 'Kebijakan Privasi']] as [$footerRoute, $footerLabel])
                        <a class="transition hover:text-lime-300" href="{{ route($footerRoute) }}">{{ $footerLabel }}</a>
                    @endforeach
                </nav>
            </div>
        </div>
    </footer>
    @unless (request()->is('admin*'))
        <div data-install-prompt hidden role="dialog" aria-labelledby="install-title"
            class="fixed inset-x-3 bottom-20 z-50 mx-auto max-w-md xl:inset-x-auto xl:bottom-6 xl:right-6 xl:w-[26rem]">
            <div class="panel bg-[#101833] p-5 shadow-2xl shadow-[#020617]/70">
                <div class="flex items-start gap-3">
                    <img class="size-12 shrink-0 rounded-2xl" src="{{ asset('images/icons/icon-192.png') }}" alt="" width="48" height="48">
                    <div class="min-w-0 flex-1">
                        <h2 id="install-title" class="font-bold">Pasang Aplikasi ZutoMarket</h2>
                        <p class="muted text-xs">Gratis, ringan, langsung dari home screen kamu.</p>
                    </div>
                    <button type="button" class="-m-1 p-1 text-slate-400 transition hover:text-white" data-install-dismiss aria-label="Tutup">✕</button>
                </div>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ([['Akses kilat', 'buka ZutoMarket langsung dari home screen, tanpa mengetik alamat.', 'M13 2 4 14h7l-1 8 9-12h-7l1-8Z'], ['Tampilan penuh', 'layar penuh seperti aplikasi, tanpa address bar.', 'M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3'], ['Selalu terbaru', 'katalog, flash sale, dan rental langsung dari website.', 'M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5']] as [$title, $description, $icon])
                        <li class="flex gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-lime-300/10 text-lime-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg></span>
                            <p class="text-slate-300"><strong class="text-white">{{ $title }}</strong> — {{ $description }}</p>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 rounded-xl bg-white/5 p-3 text-xs text-slate-300" data-install-ios hidden>Di iPhone: ketuk tombol <strong>Bagikan</strong> di Safari, lalu pilih <strong>Tambah ke Layar Utama</strong>.</p>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button type="button" class="btn-secondary" data-install-dismiss>Nanti saja</button>
                    <button type="button" class="btn" data-install-accept>⬇ Pasang Sekarang</button>
                </div>
            </div>
        </div>
        <nav class="fixed inset-x-3 bottom-3 z-40 mx-auto max-w-md rounded-2xl border border-white/20 bg-[#34416e]/70 p-1 shadow-xl shadow-[#020617]/50 backdrop-blur-xl xl:hidden"
            aria-label="Navigasi bawah">
            <div class="grid grid-cols-5">
                @foreach (['catalog' => ['Home', ['catalog', 'catalog.all', 'categories.show']], 'rental' => ['Rental Akun', ['rental']], 'joki' => ['Joki ML', ['joki']], 'about' => ['Tentang', ['about']], 'profile' => ['Akun', ['profile']]] as $route => [$label, $activeRoutes])
                    @php($isActive = request()->routeIs(...$activeRoutes))
                    @php($isCenter = $route === 'joki')
                    <a href="{{ route($route) }}"
                        class="relative flex min-h-12 flex-col items-center gap-0.5 rounded-xl text-[10px] font-semibold transition {{ $isCenter ? 'justify-end pb-1.5 font-bold text-amber-300' : 'justify-center ' . ($isActive ? 'bg-white/15 text-white' : 'text-slate-300 hover:text-white') }}"
                        @if ($isActive) aria-current="page" @endif>
                        <span class="{{ $isCenter ? 'absolute -top-6 size-12 rounded-full border-[3px] border-[#070b1c] bg-amber-300 text-slate-950 shadow-lg shadow-amber-300/30' : 'relative h-5 w-10' }} flex items-center justify-center">
                            <svg class="{{ $isCenter ? 'h-6 w-6' : 'h-5 w-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                @switch($route)
                                    @case('catalog')
                                        <path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-8.5Z" />
                                    @break

                                    @case('joki')
                                        <path
                                            d="M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7Z" />
                                        <path d="M7.5 10v5M5 12.5h5" />
                                        <circle cx="16" cy="11" r=".75" fill="currentColor" stroke="none" />
                                        <circle cx="18" cy="14" r=".75" fill="currentColor" stroke="none" />
                                    @break

                                    @case('about')
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 16v-4M12 8h.01" />
                                    @break

                                    @case('profile')
                                        <path d="M20 21v-1a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                                    @break

                                    @case('rental')
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 7v5l3 2" />
                                    @break
                                @endswitch
                            </svg>
                        </span>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endunless
</body>

</html>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

<body class="min-h-screen flex flex-col {{ request()->is('admin*') ? '' : 'pb-24 xl:pb-0' }}">
    @php($storeName = \App\Models\Setting::valueFor('store_name', 'SHOPGAME ZUTO'))
    <header
        class="sticky top-0 z-40 border-b border-[#a9c3ff]/15 bg-[#070b1c]/90 shadow-lg shadow-[#020617]/10 backdrop-blur-xl">
        <div class="shell flex h-20 items-center justify-between gap-4">
            <a href="{{ route('catalog') }}" class="flex min-w-0 items-center gap-3"
                aria-label="{{ $storeName }}"><img src="{{ asset('images/zuto-wordmark.webp') }}"
                    alt="{{ $storeName }}" width="1199" height="422" class="brand-wordmark"><span
                    class="sr-only">{{ $storeName }}</span></a>
            @unless (request()->is('admin*'))
            <nav class="hidden items-center gap-4 xl:flex" aria-label="Navigasi utama"><a
                    class="nav-link {{ request()->routeIs('catalog', 'categories.show') ? 'text-white bg-white/5' : '' }}"
                    href="{{ route('catalog') }}">Home</a><a
                    class="nav-link {{ request()->routeIs('rental') ? 'text-white bg-white/5' : '' }}"
                    href="{{ route('rental') }}">Rental Akun</a><a
                    class="nav-link {{ request()->routeIs('joki') ? 'text-white bg-white/5' : '' }}"
                    href="{{ route('joki') }}">Joki ML</a><a
                    class="nav-link {{ request()->routeIs('about') ? 'text-white bg-white/5' : '' }}"
                    href="{{ route('about') }}">Tentang</a><a
                    class="nav-link {{ request()->routeIs('profile') ? 'text-white bg-white/5' : '' }}"
                    href="{{ route('profile') }}">Akun</a>
            </nav>
            @endunless
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
    <footer class="mt-16 border-t border-[#a9c3ff]/15 py-8">
        <div class="shell flex flex-col justify-between gap-4 sm:flex-row">
            <div class="flex items-center gap-3">
                <div><img src="{{ asset('images/zuto-wordmark.webp') }}" alt="{{ $storeName }}" width="1199"
                        height="422" class="brand-wordmark">
                    <p class="muted mt-1">Akun pilihan Petualangan baru</p>
                </div>
            </div>
            <div class="w-full sm:max-w-md sm:text-right">
                <p class="muted">© {{ date('Y') }} {{ $storeName }}.</p>
                <a href="{{ route('how') }}"
                    class="mt-1 inline-block text-sm text-slate-400 transition hover:text-lime-300">Transaksi
                    dilanjutkan melalui WhatsApp ↗</a>
                <p class="mt-1 text-xs text-slate-500">Created By OzinVoid</p>
                <nav class="mt-4 grid gap-2 sm:grid-cols-2" aria-label="Ikuti {{ $storeName }}">
                    <a href="https://whatsapp.com/channel/0029VbE83IN3mFXxE4NlTG0L" target="_blank"
                        rel="noopener noreferrer"
                        class="group flex items-center gap-3 rounded-xl border border-[#a9c3ff]/15 bg-white/[0.03] p-3 text-left transition hover:border-lime-300/40 hover:bg-lime-300/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lime-300">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-lime-300/10 text-lime-300"
                            aria-hidden="true">↗</span>
                        <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-100">Saluran
                                WhatsApp</span><span class="block truncate text-xs text-slate-400">Ikuti update
                                terbaru</span></span>
                    </a>
                    <a href="https://www.instagram.com/zutomarket.id" target="_blank" rel="noopener noreferrer"
                        class="group flex items-center gap-3 rounded-xl border border-[#a9c3ff]/15 bg-white/[0.03] p-3 text-left transition hover:border-lime-300/40 hover:bg-lime-300/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lime-300">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-lime-300/10 text-lime-300"
                            aria-hidden="true">◎</span>
                        <span class="min-w-0 flex-1"><span
                                class="block text-sm font-semibold text-slate-100">Instagram</span><span
                                class="block truncate text-xs text-slate-400">@zutomarket.id</span></span>
                    </a>
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
                @foreach (['catalog' => ['Home', ['catalog', 'categories.show']], 'rental' => ['Rental Akun', ['rental']], 'joki' => ['Joki ML', ['joki']], 'about' => ['Tentang', ['about']], 'profile' => ['Akun', ['profile']]] as $route => [$label, $activeRoutes])
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

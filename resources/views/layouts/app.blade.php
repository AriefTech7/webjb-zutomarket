<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZutoShopGame') · {{ \App\Models\Setting::valueFor('store_name', 'SHOPGAME ZUTO') }}</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/zuto-maskot.webp') }}">
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

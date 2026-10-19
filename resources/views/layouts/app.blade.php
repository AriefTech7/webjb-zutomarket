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

<body class="min-h-screen flex flex-col">
    @php($storeName = \App\Models\Setting::valueFor('store_name', 'SHOPGAME ZUTO'))
    @php($whatsappNumber = \App\Models\Setting::valueFor('whatsapp_number'))
    <header class="border-b border-[#a9c3ff]/15 bg-[#070b1c]/90 backdrop-blur-xl">
        <div class="shell flex h-20 items-center justify-between gap-4">
            <a href="{{ route('catalog') }}" class="flex min-w-0 items-center gap-3"
                aria-label="{{ $storeName }}"><img src="{{ asset('images/zuto-wordmark.webp') }}"
                    alt="{{ $storeName }}" width="1199" height="422" class="brand-wordmark"><span
                    class="sr-only">{{ $storeName }}</span></a>
            <nav class="hidden items-center gap-4 md:flex" aria-label="Navigasi utama"><a
                    class="nav-link {{ request()->routeIs('catalog', 'categories.show') ? 'text-white bg-white/5' : '' }}"
                    href="{{ route('catalog') }}">Jelajahi akun</a><a
                    class="nav-link {{ request()->routeIs('trade.*') ? 'text-white bg-white/5' : '' }}"
                    href="{{ route('trade.create') }}">Tukar tambah</a><a class="nav-link"
                    href="{{ route('how') }}">Cara transaksi</a>
                @if ($whatsappNumber)
                    <a class="btn-secondary" href="https://wa.me/{{ $whatsappNumber }}" target="_blank"
                        rel="noopener noreferrer">Kontak admin ↗</a>
                @endif
                <a class="btn-secondary" href="{{ route(auth()->check() ? 'admin.dashboard' : 'login') }}">
                    {{ auth()->check() ? 'Dashboard' : 'Admin area' }} ↗</a>
            </nav>
            <button class="btn-secondary md:hidden" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu"
                aria-label="Buka menu">☰</button>
        </div>
        <nav id="mobile-menu" data-mobile-menu class="shell hidden space-y-2 pb-5 md:hidden"
            aria-label="Navigasi mobile"><a class="nav-link block" href="{{ route('catalog') }}">Jelajahi akun</a><a
                class="nav-link block" href="{{ route('trade.create') }}">Tukar tambah</a><a class="nav-link block"
                href="{{ route('how') }}">Cara transaksi</a>
            @if ($whatsappNumber)
                <a class="nav-link block text-lime-300" href="https://wa.me/{{ $whatsappNumber }}" target="_blank"
                    rel="noopener noreferrer">Kontak admin ↗</a>
            @endif
            <a class="nav-link block" href="{{ route('admin.dashboard') }}">
                Admin area</a>
        </nav>
    </header>
    <main class="flex-1">
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
            <div class="flex items-center gap-3"><img src="{{ asset('images/zuto-maskot.webp') }}"
                    alt="Maskot {{ $storeName }}" width="48" height="48" class="brand-mascot" loading="lazy">
                <div><img src="{{ asset('images/zuto-wordmark.webp') }}" alt="{{ $storeName }}" width="1199"
                        height="422" class="brand-wordmark">
                    <p class="muted mt-1">Akun pilihan. Petualangan baru.</p>
                </div>
            </div>
            <div class="muted sm:text-right">
                <p>© {{ date('Y') }} {{ $storeName }}. All rights reserved.</p><a href="{{ route('how') }}"
                    class="hover:text-lime-300">Transaksi dilanjutkan melalui WhatsApp ↗</a>
            </div>
        </div>
    </footer>
</body>

</html>

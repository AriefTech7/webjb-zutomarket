@extends('layouts.app')
@section('title', 'Akun saya')
@section('content')
    @guest
        <section class="shell py-8 md:py-14">
            @include('auth.partials.login-card')
            <nav class="muted mx-auto mt-5 flex max-w-md flex-wrap justify-center gap-x-4 gap-y-2 text-xs" aria-label="Menu akun">
                <a class="hover:text-lime-300" href="{{ route('wishlist') }}">Wishlist saya <span data-wishlist-count hidden class="rounded-full bg-rose-400 px-1.5 text-[10px] text-slate-950">0</span></a>
                <a class="hover:text-lime-300" href="{{ route('how') }}">Cara transaksi</a>
                <a class="hover:text-lime-300" href="{{ route('about') }}">WA Admin &amp; Sosmed</a>
                <a class="hover:text-lime-300" href="{{ route('feedback') }}">Kritik &amp; saran</a>
            </nav>
        </section>
    @else
        @php
            $user = auth()->user();
            $displayName = $user->is_admin ? ($user->username ?: $user->name) : $user->name;
            $words = preg_split('/\s+/', trim($displayName)) ?: [''];
            $initials = mb_strtoupper(count($words) > 1 ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1) : mb_substr($words[0], 0, 2));
            $menu = $user->is_admin
                ? [[route('admin.dashboard'), 'Dashboard admin', 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z']]
                : [
                    [route('wishlist'), 'Wishlist', 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z'],
                    [route('account.settings'), 'Pengaturan', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z'],
                    [route('account.password'), 'Ubah Password', 'M15.5 7.5a3.5 3.5 0 1 1-3.4 4.3L4 20H2v-3l8.2-8.1a3.5 3.5 0 0 1 5.3-1.4ZM17 7h.01'],
                    [route('how'), 'Cara Transaksi', 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M9 12h6M9 16h4'],
                    [route('about'), 'WA Admin & Sosmed', 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z'],
                    [route('feedback'), 'Kritik & Saran', 'M4 4h16v12H5.2L4 17.2V4ZM8 9h8M8 12h5'],
                ];
        @endphp
        <x-account-frame title="Profil">
            <div class="mt-6 text-center">
                <div class="mx-auto flex size-24 items-center justify-center rounded-full border border-lime-300/30 bg-lime-300/10 text-3xl font-black text-lime-300" aria-hidden="true">{{ $initials }}</div>
                <h2 class="mt-4 text-2xl font-black">{{ $displayName }}</h2>
                <p class="mt-1 break-all text-slate-300">{{ $user->email }}</p>
                <p class="muted text-sm">{{ $user->is_admin ? 'Admin toko' : 'via '.($user->google_id ? 'Google' : 'Email') }}</p>
            </div>
            @unless ($user->is_admin)
                <div class="mt-6 grid grid-cols-3 gap-2 sm:gap-3">
                    @foreach ([[count($user->wishlistIds()), 'Wishlist', true], [$user->created_at?->format('m/Y') ?? '-', 'Bergabung', false], [$user->whatsapp ? 'Ada' : 'Belum', 'No. WhatsApp', false]] as [$statValue, $statLabel, $isWishlist])
                        <div class="rounded-2xl border border-[#a9c3ff]/25 bg-[#101833] px-2 py-4 text-center">
                            <p class="text-xl font-black text-white sm:text-2xl" @if ($isWishlist) data-wishlist-total @endif>{{ $statValue }}</p>
                            <p class="muted mt-1 text-[11px] font-semibold sm:text-xs">{{ $statLabel }}</p>
                        </div>
                    @endforeach
                </div>
            @endunless
            <nav class="mt-6 space-y-2.5" aria-label="Menu akun">
                @foreach ($menu as [$menuUrl, $menuLabel, $menuIcon])
                    <a href="{{ $menuUrl }}" class="flex min-h-13 items-center gap-3 rounded-full border border-[#a9c3ff]/25 bg-[#101833]/80 px-5 font-bold transition hover:border-lime-300/50 hover:bg-[#16204a]">
                        <svg class="h-5 w-5 shrink-0 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $menuIcon }}" /></svg>
                        <span class="flex-1">{{ $menuLabel }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 18 6-6-6-6" /></svg>
                    </a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('account.logout') }}" class="mt-5" data-account-logout>
                @csrf
                <button class="flex min-h-13 w-full items-center justify-center gap-2 rounded-full border border-rose-400/40 font-bold text-rose-300 transition hover:bg-rose-400/10">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" /></svg>Keluar
                </button>
            </form>
        </x-account-frame>
    @endguest
@endsection

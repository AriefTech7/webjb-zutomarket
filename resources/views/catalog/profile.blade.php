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
    <section class="shell max-w-2xl py-10">
        <p class="eyebrow">Akun saya</p>
        <h1 class="mt-3 text-4xl font-black">{{ auth()->user()->is_admin ? 'Profil saya' : 'Halo, '.auth()->user()->name }}<span class="text-lime-300">.</span></h1>
            @if (auth()->user()->is_admin)
                <div class="panel mt-8 flex flex-wrap items-center justify-between gap-4 p-6">
                    <p class="text-sm text-slate-300">Kamu masuk sebagai <strong class="text-white">admin</strong> ({{ auth()->user()->username }}).</p>
                    <div class="flex gap-3"><a class="btn" href="{{ route('admin.dashboard') }}">Dashboard</a>
                        <form method="POST" action="{{ route('account.logout') }}" data-account-logout>@csrf<button class="btn-secondary">Keluar</button></form></div>
                </div>
            @else
                <p class="muted mt-4">Profil ini tersimpan di akunmu dan otomatis ditambahkan ke pesan WhatsApp saat kamu memesan.</p>
                <form method="POST" action="{{ route('account.profile') }}" class="panel mt-8 space-y-5 p-6">
                    @csrf @method('PUT')
                    <p class="text-sm text-slate-400">Masuk sebagai <strong class="text-white">{{ auth()->user()->email }}</strong></p>
                    <div><label class="label" for="account-name">Nama</label><input id="account-name" class="field" type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="50" autocomplete="name"></div>
                    <div><label class="label" for="account-whatsapp">Nomor WhatsApp</label><input id="account-whatsapp" class="field" type="tel" name="whatsapp" value="{{ old('whatsapp', auth()->user()->whatsapp) }}" maxlength="20" autocomplete="tel" placeholder="08xxxxxxxxxx"></div>
                    <button class="btn">Simpan profil</button>
                </form>
                <form method="POST" action="{{ route('account.logout') }}" class="mt-3" data-account-logout>@csrf<button class="btn-secondary w-full">Keluar dari akun</button></form>
            @endif
        <nav class="mt-6 space-y-3" aria-label="Menu akun">
            @foreach ([['wishlist', 'Wishlist saya', 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z'], ['how', 'Cara transaksi', 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'], ['about', 'WA Admin & Sosmed', 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z'], ['feedback', 'Kritik & saran', 'M4 4h16v12H5.2L4 17.2V4ZM8 9h8M8 12h5']] as [$route, $label, $icon])
                <a class="flex min-h-16 items-center justify-between gap-4 rounded-2xl border border-[#a9c3ff]/15 bg-linear-to-r from-[#2a2350] to-[#121a3d] px-5 py-3 font-bold transition hover:border-lime-300/40" href="{{ route($route) }}">
                    <span>{{ $label }}@if ($route === 'wishlist') <span data-wishlist-count hidden class="ml-1 rounded-full bg-rose-400 px-2 text-xs text-slate-950">0</span>@endif</span>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lime-300/10 text-lime-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg></span>
                </a>
            @endforeach
        </nav>
    </section>
    @endguest
@endsection

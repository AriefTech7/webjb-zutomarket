@extends('layouts.app')
@section('title', 'Akun saya')
@section('content')
    <section class="shell max-w-2xl py-10">
        <p class="eyebrow">Akun saya</p>
        <h1 class="mt-3 text-4xl font-black">Profil saya<span class="text-lime-300">.</span></h1>
        <p class="muted mt-4">Tanpa login. Data ini hanya tersimpan di browser ini dan otomatis ditambahkan ke pesan WhatsApp saat kamu memesan.</p>
        <form class="panel mt-8 space-y-5 p-6" data-profile-form>
            <div><label class="label" for="profile-name">Nama</label><input id="profile-name" class="field" type="text" name="name" maxlength="50" autocomplete="name" placeholder="Nama kamu"></div>
            <div><label class="label" for="profile-phone">Nomor WhatsApp</label><input id="profile-phone" class="field" type="tel" name="phone" maxlength="20" autocomplete="tel" placeholder="08xxxxxxxxxx"></div>
            <div class="flex flex-wrap gap-3"><button class="btn">Simpan</button><button type="button" class="btn-secondary" data-profile-clear>Hapus data</button></div>
            <p data-profile-status role="status" aria-live="polite" class="muted hidden"></p>
        </form>
        <noscript><p class="panel mt-6 p-5">Aktifkan JavaScript agar profil dapat disimpan di browser ini.</p></noscript>
        <nav class="mt-6 space-y-3" aria-label="Menu akun">
            @foreach ([['wishlist', 'Wishlist saya', 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z'], ['how', 'Cara transaksi', 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'], ['about', 'WA Admin & Sosmed', 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z']] as [$route, $label, $icon])
                <a class="flex min-h-16 items-center justify-between gap-4 rounded-2xl border border-[#a9c3ff]/15 bg-linear-to-r from-[#2a2350] to-[#121a3d] px-5 py-3 font-bold transition hover:border-lime-300/40" href="{{ route($route) }}">
                    <span>{{ $label }}@if ($route === 'wishlist') <span data-wishlist-count hidden class="ml-1 rounded-full bg-rose-400 px-2 text-xs text-slate-950">0</span>@endif</span>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lime-300/10 text-lime-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg></span>
                </a>
            @endforeach
        </nav>
    </section>
@endsection

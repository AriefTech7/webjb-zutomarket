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
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <a class="btn-secondary" href="{{ route('wishlist') }}">♡ Wishlist saya</a>
            <a class="btn-secondary" href="{{ route('how') }}">Cara transaksi</a>
        </div>
    </section>
@endsection

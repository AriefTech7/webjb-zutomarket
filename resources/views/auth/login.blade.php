@extends('layouts.app')
@section('title', 'Login admin')
@section('content')
<section class="shell py-10 md:py-16">
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl border border-[#a9c3ff]/20 bg-[#101833]/85 shadow-2xl shadow-[#020617]/40 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="relative hidden min-h-[34rem] overflow-hidden bg-[#0b1431] p-10 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-40" style="background: radial-gradient(circle at 20% 10%, #839cff 0%, transparent 35%), radial-gradient(circle at 90% 85%, #8e7bff 0%, transparent 40%)"></div>
            <div class="relative">
                <span class="status text-lime-300">Area terbatas</span>
                <h1 class="mt-6 max-w-md text-4xl font-black leading-tight">Kelola katalog toko dalam satu tempat.</h1>
                <p class="muted mt-4 max-w-md">Tambah akun, atur kategori, perbarui status stok, dan arahkan pembeli ke WhatsApp.</p>
            </div>
            <div class="relative grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-2xl">◈</p><p class="mt-3 font-semibold">Katalog terkontrol</p><p class="muted mt-1 text-xs">Draft, tersedia, atau terjual.</p></div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-2xl">⌁</p><p class="mt-3 font-semibold">Kontak langsung</p><p class="muted mt-1 text-xs">Pesanan dilanjutkan via WhatsApp.</p></div>
            </div>
        </div>
        <form method="POST" action="{{ route('login') }}" class="flex flex-col justify-center p-6 sm:p-10 lg:p-12">
            @csrf
            <div class="mb-8">
                <p class="eyebrow">Admin area</p>
                <h2 class="mt-3 text-3xl font-black">Selamat datang kembali<span class="text-lime-300">.</span></h2>
                <p class="muted mt-3">Masukkan username dan password administrator.</p>
            </div>
            <div class="space-y-5">
                <div><label for="username" class="label">Username</label><input id="username" name="username" type="text" class="field" value="{{ old('username') }}" required maxlength="50" autocomplete="username" autofocus placeholder="Masukkan username"></div>
                <div><label for="password" class="label">Password</label><input id="password" name="password" type="password" class="field" required autocomplete="current-password" placeholder="Masukkan password"></div>
            </div>
            <button class="btn mt-7 w-full">Masuk ke dashboard <span aria-hidden="true">→</span></button>
            <p class="muted mt-5 text-center text-xs">Akses hanya untuk administrator toko.</p>
        </form>
    </div>
</section>
@endsection

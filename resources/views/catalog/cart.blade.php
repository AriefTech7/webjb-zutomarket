@extends('layouts.app')
@section('title', 'Keranjang')
@section('content')
<section class="shell py-10" data-cart-page data-cart-endpoint="{{ route('cart.accounts') }}">
    <p class="eyebrow">Siap dibeli</p>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-4"><h1 class="text-3xl font-black md:text-4xl">Keranjang <span class="text-lime-300">🛒</span></h1><a class="btn-secondary" href="{{ route('catalog.all') }}">Tambah akun lain ↗</a></div>
    <p class="muted mt-4 max-w-2xl">Keranjang tersimpan di browser ini (maksimal {{ \App\Http\Controllers\CartController::LIMIT }} akun). Akun milik toko dibayar langsung di website (QRIS, virtual account, e-wallet); akun titipan penjual dipesan lewat WhatsApp penjualnya.</p>
    <p data-cart-loading role="status" class="muted mt-8">Memuat keranjang…</p>
    <div data-cart-error role="alert" class="hidden mt-6 rounded-xl border border-rose-300/30 bg-rose-300/5 p-5"><p data-cart-error-message>Keranjang belum dapat dimuat. Pilihanmu tetap tersimpan.</p><button type="button" data-cart-retry class="btn-secondary mt-3">Coba lagi</button></div>
    <div data-cart-empty class="panel hidden mt-8 p-10 text-center"><p class="text-4xl" aria-hidden="true">🛒</p><h2 class="mt-4 text-xl font-bold">Keranjang masih kosong</h2><p class="muted mt-2">Tekan tombol Keranjang pada akun yang ingin kamu beli.</p><a class="btn mt-5" href="{{ route('catalog.all') }}">Mulai belanja ↗</a></div>
    <div data-cart-items class="mt-8" aria-busy="true"></div>
    <noscript><p class="panel mt-6 p-5">Aktifkan JavaScript agar keranjang dapat disimpan dan ditampilkan di browser ini.</p></noscript>
</section>
@endsection

@extends('layouts.app')
@section('title', 'Wishlist saya')
@section('content')
<section class="shell py-10" data-wishlist-page data-wishlist-endpoint="{{ route('wishlist.accounts') }}">
    <p class="eyebrow">Pilihan favoritmu</p>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-4"><h1 class="text-3xl font-black md:text-4xl">Wishlist saya <span class="text-lime-300">♡</span></h1><a class="btn-secondary" href="{{ route('catalog') }}">Jelajahi akun ↗</a></div>
    <p class="muted mt-4 max-w-2xl">Simpan hingga 100 akun tanpa login. Wishlist hanya tersimpan di browser ini, tidak tersinkron ke perangkat lain, dan hilang jika data situs dihapus.</p>
    <p data-wishlist-loading role="status" class="muted mt-8">Memuat wishlist…</p>
    <div data-wishlist-error role="alert" class="hidden mt-6 rounded-xl border border-rose-300/30 bg-rose-300/5 p-5"><p data-wishlist-error-message>Wishlist belum dapat dimuat. Pilihanmu tetap tersimpan.</p><button type="button" data-wishlist-retry class="btn-secondary mt-3">Coba lagi</button></div>
    <div data-wishlist-empty class="panel hidden mt-8 p-10 text-center"><p class="text-4xl text-rose-300" aria-hidden="true">♡</p><h2 class="mt-4 text-xl font-bold">Wishlist masih kosong</h2><p class="muted mt-2">Tekan tombol hati pada akun yang kamu sukai, lalu temukan kembali di sini.</p><a class="btn mt-5" href="{{ route('catalog') }}">Mulai jelajahi akun ↗</a></div>
    <div data-wishlist-grid class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" aria-busy="true"></div>
    <noscript><p class="panel mt-6 p-5">Aktifkan JavaScript agar wishlist dapat disimpan dan ditampilkan di browser ini.</p></noscript>
</section>
@endsection

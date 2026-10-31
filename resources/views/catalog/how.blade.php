@extends('layouts.app')
@section('title', 'Cara transaksi')
@section('content')
    <section class="shell py-14">
        <h1 class="mt-3 text-4xl font-black">Akun baru, dalam 3 langkah<span class="text-lime-300">.</span></h1>
        <div class="mt-9 grid gap-5 md:grid-cols-3">
            @foreach (['Pilih akun favorit' => 'Gunakan pencarian dan kategori. Baca spesifikasi, harga, dan gambar pada halaman detail akun.', 'Hubungi admin' => 'Klik tombol WhatsApp di halaman detail. Pesan berisi nama dan harga akun akan disiapkan otomatis.', 'Selesaikan transaksi' => 'Konfirmasi ketersediaan, metode pembayaran, dan proses serah terima langsung dengan admin melalui WhatsApp.'] as $title => $description)
                <article class="panel p-7"><span class="text-4xl font-black text-lime-300/70">0{{ $loop->iteration }}</span>
                    <h2 class="mb-3 mt-6 text-xl font-bold">{{ $title }}</h2>
                    <p class="muted">{{ $description }}</p>
                </article>
            @endforeach
        </div><a href="{{ route('catalog') }}" class="btn mt-8">Mulai jelajahi akun ↗</a>
    </section>
@endsection

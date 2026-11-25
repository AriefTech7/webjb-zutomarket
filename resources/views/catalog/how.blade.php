@extends('layouts.app')
@section('title', 'Cara transaksi')
@section('content')
    <section class="shell py-14">
        <h1 class="mt-3 text-4xl font-black">Akun baru, dalam 3 langkah<span class="text-lime-300">.</span></h1>
        <div class="mt-9 grid gap-5 md:grid-cols-3">
            @foreach ([
                'Pilih akun favorit' => 'Gunakan pencarian dan kategori. Baca spesifikasi, harga, dan gambar di halaman detail akun. Ada pertanyaan? Tekan "Tanya Stok" untuk live chat dengan admin.',
                'Bayar di website' => 'Masuk ke akunmu, tekan "Beli Sekarang", pakai kode voucher jika punya, lalu bayar lewat QRIS, virtual account bank, e-wallet, atau minimarket. Status pembayaran diperbarui otomatis.',
                'Terima akun' => 'Setelah pembayaran lunas, admin mengirim data login akun lewat live chat (atau WhatsApp). Segera ganti password dan amankan email/nomor yang terhubung.',
            ] as $title => $description)
                <article class="panel p-7"><span class="text-4xl font-black text-lime-300/70">0{{ $loop->iteration }}</span>
                    <h2 class="mb-3 mt-6 text-xl font-bold">{{ $title }}</h2>
                    <p class="muted">{{ $description }}</p>
                </article>
            @endforeach
        </div>
        <p class="muted mt-6 max-w-3xl text-sm">Semua pembelian dibayar penuh (lunas). Akun dari penjual mitra yang belum bisa dibayar di website dipesan lewat tombol WhatsApp di halaman akunnya. Lihat juga <a class="font-bold text-lime-300 hover:underline" href="{{ route('terms') }}">Ketentuan Layanan</a> dan <a class="font-bold text-lime-300 hover:underline" href="{{ route('refund') }}">Kebijakan Refund</a>.</p>
        <a href="{{ route('catalog') }}" class="btn mt-8">Mulai jelajahi akun ↗</a>
    </section>
@endsection

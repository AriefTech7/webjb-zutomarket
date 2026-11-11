@extends('layouts.app')
@section('title', 'Kebijakan Privasi')
@section('content')
    <section class="shell max-w-3xl py-8 md:py-12">
        <nav class="muted text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Home</a> / <span class="text-slate-300">Kebijakan Privasi</span></nav>
        <h1 class="mt-3 text-3xl font-black md:text-4xl">Kebijakan Privasi</h1>
        <div class="panel mt-6 space-y-6 p-6 text-sm leading-7 text-slate-300 md:p-8">
            <div>
                <h2 class="text-lg font-bold text-white">Data yang kami simpan</h2>
                <p class="mt-2">Saat kamu membuat akun, kami menyimpan nama, email, password yang sudah dienkripsi, nomor WhatsApp jika kamu mengisinya, dan daftar akun game di wishlist kamu. Jika kamu masuk dengan Google, kami menyimpan ID akun Google dan email dari Google. Kami tidak menerima password Google kamu.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">Untuk apa data dipakai</h2>
                <p class="mt-2">Data dipakai agar kamu bisa masuk, agar wishlist dan profil tetap ada di perangkat lain, dan agar nama serta nomor WhatsApp otomatis ditambahkan ke pesan saat kamu menghubungi admin. Transaksi tetap dilanjutkan melalui WhatsApp admin.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">Data di browser</h2>
                <p class="mt-2">Tanpa akun, wishlist dan profil hanya tersimpan di browser perangkatmu. Saat kamu keluar dari akun, salinan di browser ikut dihapus.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">Berbagi data</h2>
                <p class="mt-2">Kami tidak menjual data kamu. Data tidak dibagikan ke pihak lain kecuali diperlukan untuk menjalankan website atau diwajibkan oleh hukum.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">Hapus akun</h2>
                <p class="mt-2">Ingin data akunmu dihapus? Hubungi admin melalui halaman <a class="font-bold text-lime-300 hover:underline" href="{{ route('about') }}">Tentang</a>.</p>
            </div>
        </div>
    </section>
@endsection

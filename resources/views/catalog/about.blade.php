@extends('layouts.app')
@section('title', 'Tentang kami')
@section('description', 'ZutoMarket melayani jual beli akun game dan joki rank Mobile Legends, dengan transaksi melalui WhatsApp admin.')
@section('content')
    @php($whatsappNumber = \App\Models\Setting::valueFor('whatsapp_number'))
    <section class="shell max-w-3xl py-10">
        <p class="eyebrow">Tentang kami</p>
        <h1 class="mt-3 text-4xl font-black">ZutoMarket<span class="text-lime-300">.</span></h1>
        <p class="muted mt-4">Solusi praktis untuk kebutuhan jual beli akun game. Pilih akun dari katalog atau pesan joki rank Mobile Legends, lalu lanjutkan transaksi langsung dengan admin melalui WhatsApp.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <a class="joki-card block p-5" href="{{ route('catalog') }}">
                <h2 class="font-bold">Jual beli akun game</h2>
                <p class="muted mt-1">Lihat spesifikasi, harga, dan gambar setiap akun di katalog.</p>
            </a>
            <a class="joki-card block p-5" href="{{ route('joki') }}">
                <h2 class="font-bold">Joki rank Mobile Legends</h2>
                <p class="muted mt-1">Harga per bintang sesuai rank, dengan pilihan request hero.</p>
            </a>
        </div>
        <h2 class="mt-10 text-xl font-bold">Hubungi kami</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            @if ($whatsappNumber)
                <a class="btn" href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noopener noreferrer">WhatsApp admin ↗</a>
            @endif
            <a class="btn-secondary" href="https://www.instagram.com/zutomarket.id" target="_blank" rel="noopener noreferrer">Instagram ↗</a>
            <a class="btn-secondary" href="https://www.tiktok.com/@aku_zuto" target="_blank" rel="noopener noreferrer">TikTok ↗</a>
        </div>
    </section>
@endsection

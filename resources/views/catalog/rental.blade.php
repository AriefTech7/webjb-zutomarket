@extends('layouts.app')
@section('title', 'Rental Akun')
@section('description', 'Sewa akun game di ZutoMarket dengan paket durasi, pesan langsung melalui WhatsApp admin.')
@section('content')
    <section class="shell py-10">
        <p class="eyebrow">Sewa akun game</p>
        <h1 class="mt-3 text-4xl font-black">Rental Akun<span class="text-lime-300">.</span></h1>
        <p class="muted mt-4 max-w-2xl">Pilih akun dan paket durasi sewa, lalu pesan langsung ke admin rental melalui WhatsApp. Akun yang sedang disewa menampilkan estimasi kapan siap lagi.</p>
        <div class="mt-8 grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($accounts as $account)
                @include('catalog.rental-card', ['account' => $account])
            @empty
                <div class="panel col-span-full p-10 text-center">
                    <h2 class="text-xl font-bold">Belum ada akun rental</h2>
                    <p class="muted mt-2">Akun yang bisa disewa akan tampil di sini.</p>
                    <a class="btn mt-5" href="{{ route('catalog') }}">Jelajahi akun ↗</a>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $accounts->links() }}</div>
    </section>
@endsection

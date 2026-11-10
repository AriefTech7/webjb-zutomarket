@extends('layouts.app')
@section('title', 'Flash Sale')
@section('description', 'Akun game dengan harga spesial untuk waktu terbatas di ZutoMarket.')
@section('content')
    <section class="shell py-10">
        <p class="eyebrow">Promo waktu terbatas</p>
        <h1 class="mt-3 flex items-center gap-3 text-4xl font-black">Flash Sale <span class="text-amber-300" aria-hidden="true">⚡</span></h1>
        <p class="muted mt-4 max-w-2xl">Akun dengan harga spesial sampai waktu flash sale berakhir. Hitung mundur di setiap akun menunjukkan sisa waktunya.</p>
        <div class="mt-8 grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($accounts as $account)
                @include('catalog.account-card', ['account' => $account])
            @empty
                <div class="panel col-span-full p-10 text-center">
                    <p class="text-4xl text-amber-300" aria-hidden="true">⚡</p>
                    <h2 class="mt-4 text-xl font-bold">Belum ada flash sale saat ini</h2>
                    <p class="muted mt-2">Pantau terus, atau lihat akun lain di katalog.</p>
                    <a class="btn mt-5" href="{{ route('catalog') }}">Jelajahi akun ↗</a>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $accounts->links() }}</div>
    </section>
@endsection

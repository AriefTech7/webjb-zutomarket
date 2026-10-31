@extends('layouts.app')
@section('title', $account->title)
@section('description', \Illuminate\Support\Str::limit($account->description, 150))
@section('content')
    <div class="shell py-8">
        <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
            <nav class="muted flex flex-wrap gap-2"><a href="{{ route('catalog') }}">Katalog</a><span>/</span><a
                    href="{{ route('categories.show', $account->category->slug) }}">{{ $account->category->name }}</a><span>/
                    Detail akun</span></nav>
        </div>
        <div class="grid gap-8 lg:grid-cols-[1.2fr_1fr]">
            <div>
                <div class="panel overflow-hidden">
                    <img data-main-image src="{{ $account->imageUrl() }}" alt="{{ $account->title }}"
                        class="aspect-[4/3] w-full {{ str_starts_with($account->main_image, 'images/') ? 'object-cover' : 'object-contain' }} bg-[#080f25]">
                </div>
                @if ($account->images->isNotEmpty())
                    <div class="mt-3 grid grid-cols-4 gap-3">
                        <button type="button" data-gallery-image="{{ $account->imageUrl() }}" aria-pressed="true"
                            aria-label="Tampilkan gambar utama" class="panel overflow-hidden aria-pressed:border-lime-300">
                            <img src="{{ $account->imageUrl() }}"
                                class="aspect-square w-full bg-[#080f25] object-contain p-1" alt="Gambar utama">
                        </button>
                        @foreach ($account->images as $image)
                            <button type="button"
                                data-gallery-image="{{ Storage::disk('public')->url($image->image_path) }}"
                                aria-pressed="false" aria-label="Tampilkan gambar {{ $loop->iteration + 1 }}"
                                class="panel overflow-hidden aria-pressed:border-lime-300">
                                <img class="aspect-square w-full bg-[#080f25] object-contain p-1"
                                    src="{{ Storage::disk('public')->url($image->image_path) }}"
                                    alt="Gambar tambahan {{ $loop->iteration }}" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div>
                <p class="eyebrow mb-3">{{ $account->category->name }}</p>
                <h1 class="text-3xl font-black leading-tight md:text-4xl">{{ $account->title }}</h1><span
                    class="status my-5 {{ $account->status === 'available' ? 'text-lime-300' : 'text-slate-400' }}">{{ $account->status === 'available' ? '● Tersedia' : 'Terjual' }}</span>
                <div class="panel p-6">
                    <p class="muted">Harga akun</p>
                    <p class="my-2 text-3xl font-black text-lime-300">{{ $account->formattedPrice() }}</p>
                    <p class="muted mb-5">Konfirmasi ketersediaan dan detail transaksi dengan admin.</p>
                    @if ($url = $account->whatsappUrl())
                        <a class="btn w-full" href="{{ $url }}" target="_blank" rel="noopener noreferrer">Hubungi admin via WhatsApp ↗</a>
                    @else
                        <p class="rounded-xl bg-white/5 p-4 text-center text-sm text-slate-400">
                            {{ $account->status === 'sold' ? 'Akun ini sudah terjual.' : 'Kontak WhatsApp belum diatur oleh admin.' }}
                        </p>
                    @endif
                </div>
                <div class="mt-6">
                    <h2 class="mb-3 font-bold">Spesifikasi akun</h2>
                    <div class="panel whitespace-pre-line p-5 text-sm leading-7 text-slate-300">{{ $account->specifications }}</div>
                </div>
            </div>
        </div>
        <section class="mt-9 max-w-3xl">
            <h2 class="mb-3 text-xl font-bold">Tentang akun ini</h2>
            <p class="whitespace-pre-line leading-7 text-slate-400">{{ $account->description }}</p>
        </section>
    </div>
@endsection

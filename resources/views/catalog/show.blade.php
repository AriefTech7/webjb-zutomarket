@extends('layouts.app')
@section('title', $account->title)
@section('description', \Illuminate\Support\Str::limit($account->description, 150))
@section('content')
    <div class="shell py-6 md:py-8">
        <nav class="muted mb-5 flex flex-wrap gap-2 text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Beranda</a><span>/</span><a
                class="hover:text-white" href="{{ $account->is_rental ? route('rental') : route('catalog.all') }}">{{ $account->is_rental ? 'Rental' : 'Produk' }}</a><span>/</span><span class="text-slate-300">{{ $account->title }}</span></nav>
        <div class="grid gap-5 lg:grid-cols-[1.1fr_1fr] lg:gap-6">
            <div class="panel h-fit p-3 md:p-4">
                <div class="overflow-hidden rounded-xl bg-[#080f25]">
                    <img data-main-image src="{{ $account->imageUrl() }}" alt="{{ $account->title }}"
                        class="aspect-[4/5] w-full {{ str_starts_with($account->main_image, 'images/') ? 'object-cover' : 'object-contain' }}">
                </div>
                @if ($account->images->isNotEmpty())
                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                        <button type="button" data-gallery-image="{{ $account->imageUrl() }}" aria-pressed="true"
                            aria-label="Tampilkan gambar utama" class="size-16 shrink-0 overflow-hidden rounded-lg border-2 border-transparent bg-[#080f25] aria-pressed:border-lime-300 md:size-20">
                            <img src="{{ $account->imageUrl() }}" class="h-full w-full object-cover object-top" alt="Gambar utama">
                        </button>
                        @foreach ($account->images as $image)
                            <button type="button" data-gallery-image="{{ Storage::disk('public')->url($image->image_path) }}"
                                aria-pressed="false" aria-label="Tampilkan gambar {{ $loop->iteration + 1 }}"
                                class="size-16 shrink-0 overflow-hidden rounded-lg border-2 border-transparent bg-[#080f25] aria-pressed:border-lime-300 md:size-20">
                                <img class="h-full w-full object-cover object-top" src="{{ Storage::disk('public')->url($image->image_path) }}"
                                    alt="Gambar tambahan {{ $loop->iteration }}" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="panel h-fit p-5 md:p-6">
                <p class="text-xs font-black uppercase tracking-wide text-amber-300">{{ $account->category->name }}</p>
                <h1 class="mt-2 text-2xl font-black leading-tight md:text-4xl">{{ $account->title }}</h1>
                <span class="status mt-3 {{ $account->status === 'available' ? 'text-lime-300' : ($account->is_rental ? 'text-amber-300' : 'text-rose-300') }}">{{ $account->status === 'available' ? ($account->is_rental ? '● Siap disewa' : '● Tersedia') : ($account->is_rental ? 'Sedang disewa' : 'Terjual') }}</span>

                @if ($account->is_rental)
                    <div class="mt-5">@include('catalog.rental-panel', ['account' => $account])</div>
                @else
                    <div class="mt-5 rounded-2xl border border-lime-300/20 bg-lime-300/5 p-4 md:p-5">
                        @if ($account->status === 'sold')
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status akun</p>
                            <p class="mt-1 text-3xl font-black uppercase tracking-widest text-rose-300">Terjual</p>
                            <p class="muted mt-1">Akun ini sudah terjual. Lihat akun lain yang masih tersedia di katalog.</p>
                        @else
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Harga</p>
                            <p class="mt-1 text-3xl font-black text-lime-300 md:text-4xl">{{ $account->formattedPrice() }}</p>
                            @if ($account->hasDiscount())
                                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm"><span class="text-slate-500 line-through">{{ $account->formattedOriginalPrice() }}</span>
                                    <span class="rounded-md bg-emerald-300/15 px-2 py-0.5 text-xs font-bold text-emerald-300">Hemat {{ $account->formattedSavings() }} (-{{ $account->discountPercent() }}%)</span></p>
                            @endif
                            @if ($account->isOnFlashSale())
                                <p class="mt-3 inline-flex flex-wrap items-center gap-1.5 rounded-lg bg-amber-300/15 px-3 py-1.5 text-sm font-bold text-amber-300">⚡ Flash sale berakhir dalam <span
                                        data-countdown="{{ $account->flash_sale_ends_at->toIso8601ZuluString() }}">{{ $account->flashSaleCountdown() }}</span></p>
                            @endif
                        @endif
                    </div>
                @endif

                @if ($account->description)
                    <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-300">{{ $account->description }}</p>
                @endif
                <div class="mt-4">
                    <h2 class="mb-2 text-sm font-bold">Spesifikasi akun</h2>
                    <div class="whitespace-pre-line rounded-xl bg-white/5 p-4 text-sm leading-7 text-slate-300">{{ $account->specifications }}</div>
                </div>

                @unless ($account->is_rental)
                    <div class="mt-5 space-y-3">
                        @if ($url = $account->whatsappUrl())
                            <a class="btn-secondary w-full" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" /></svg>Tanya Stok{{ $account->seller_whatsapp ? ' ke penjual' : '' }}</a>
                            <div class="grid grid-cols-2 gap-3">
                                <a class="btn-secondary w-full border-lime-300/50 bg-transparent text-lime-300" href="{{ $account->offerWhatsappUrl() }}" target="_blank" rel="noopener noreferrer">Tawar</a>
                                <a class="btn w-full" href="{{ $account->buyWhatsappUrl() }}" target="_blank" rel="noopener noreferrer">Beli</a>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <a class="btn-secondary w-full" href="{{ $account->tradeInWhatsappUrl() }}" target="_blank" rel="noopener noreferrer">Tukar tambah</a>
                                <a class="btn-secondary w-full" href="{{ $account->installmentWhatsappUrl() }}" target="_blank" rel="noopener noreferrer">Cicilan</a>
                            </div>
                        @elseif ($account->status === 'sold')
                            <a class="btn w-full" href="{{ route('catalog.all') }}">Lihat akun lain ↗</a>
                        @else
                            <p class="rounded-xl bg-white/5 p-4 text-center text-sm text-slate-400">Kontak WhatsApp belum diatur oleh admin.</p>
                        @endif
                        <div class="grid">@include('catalog.wishlist-button', ['account' => $account])</div>
                    </div>
                @endunless
            </div>
        </div>

        @if ($recommendations->isNotEmpty())
            <section class="mt-10" aria-labelledby="recommendations">
                <h2 id="recommendations" class="mb-4 text-xl font-black md:text-2xl">Rekomendasi Produk</h2>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                    @foreach ($recommendations as $recommended)
                        @include('catalog.account-card', ['account' => $recommended])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

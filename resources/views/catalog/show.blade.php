@extends('layouts.app')
@section('title', $account->title)
@section('description', \Illuminate\Support\Str::limit($account->description, 150))
@section('content')
    <div class="shell py-6 md:py-8">
        <nav class="muted mb-5 flex flex-wrap gap-2 text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Beranda</a><span>/</span><a
                class="hover:text-white" href="{{ $account->is_rental ? route('rental') : route('catalog.all') }}">{{ $account->is_rental ? 'Rental' : 'Produk' }}</a><span>/</span><span class="text-slate-300">{{ $account->title }}</span></nav>
        <div class="grid gap-5 lg:grid-cols-[1.1fr_1fr] lg:gap-6">
            <div class="panel flex min-w-0 flex-col p-3 md:p-4">
                <div class="relative overflow-hidden rounded-xl bg-[#080f25] lg:min-h-[28rem] lg:flex-1">
                    <img data-main-image src="{{ $account->imageUrl() }}" alt="{{ $account->title }}"
                        class="aspect-[4/5] w-full lg:absolute lg:inset-0 lg:aspect-auto lg:h-full {{ str_starts_with($account->main_image, 'images/') ? 'object-cover' : 'object-contain' }}">
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

            <div class="panel min-w-0 p-5 md:p-6">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <p class="text-xs font-black uppercase tracking-wide text-amber-300">{{ $account->category->name }}</p>
                    @include('catalog.card-badges')
                    @if ($account->isBooked())
                        <span class="status text-amber-300">{{ $account->publicStatusLabel() }}</span>
                    @else
                        @if ($account->status === 'available')
                            <span class="status status-live text-lime-300">@include('catalog.live-dot'){{ $account->is_rental ? 'Siap disewa' : 'Tersedia' }}</span>
                        @else
                            <span class="status {{ $account->is_rental ? 'text-amber-300' : 'text-rose-300' }}">{{ $account->is_rental ? 'Sedang disewa' : 'Terjual' }}</span>
                        @endif
                    @endif
                </div>
                <h1 class="mt-2 text-2xl font-black leading-tight md:text-4xl">{{ $account->title }}</h1>

                {{-- Share / Suka / Keranjang --}}
                <div class="mt-4 grid grid-cols-3 gap-2 md:gap-3">
                    <button type="button" class="detail-action" data-share-url="{{ route('accounts.show', $account->slug) }}" data-share-title="{{ $account->title }}" aria-label="Bagikan {{ $account->title }}">
                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4" /></svg>
                        <span data-share-label>Share</span>
                    </button>
                    @include('catalog.wishlist-button', ['account' => $account, 'variant' => 'detail'])
                    @if ($account->status === 'available' && ! $account->is_rental)
                        <button type="button" class="detail-action aria-pressed:border-lime-300/50 aria-pressed:text-lime-300" data-cart-toggle="{{ $account->id }}" data-account-title="{{ $account->title }}"
                            aria-pressed="false" aria-label="Tambahkan {{ $account->title }} ke keranjang" disabled>
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1.5" /><circle cx="18" cy="20" r="1.5" /><path d="M2.5 3h2.6l2.4 12h11l2-8H6.3" /></svg>
                            <span>Keranjang</span>
                        </button>
                    @else
                        <span class="detail-action cursor-not-allowed opacity-45" title="Akun ini tidak bisa dimasukkan ke keranjang">
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1.5" /><circle cx="18" cy="20" r="1.5" /><path d="M2.5 3h2.6l2.4 12h11l2-8H6.3" /></svg>
                            <span>Keranjang</span>
                        </span>
                    @endif
                </div>

                @if ($account->is_rental)
                    <div class="mt-5">@include('catalog.rental-panel', ['account' => $account])</div>
                @elseif ($account->status === 'sold' || $account->isBooked())
                    <div class="mt-5 rounded-2xl border border-white/10 bg-white/5 p-4 md:p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status akun</p>
                        @if ($account->status === 'sold')
                            <p class="mt-1 text-3xl font-black uppercase tracking-widest text-rose-300">Terjual</p>
                            <p class="muted mt-1">Akun ini sudah terjual. Lihat akun lain yang masih tersedia di katalog.</p>
                        @else
                            <p class="mt-1 text-3xl font-black uppercase tracking-widest text-amber-300">{{ $account->publicStatusLabel() }}</p>
                            <p class="muted mt-1">Akun ini sudah dipesan pembeli dan tidak bisa dipesan lagi. Harga: {{ $account->formattedPrice() }}.</p>
                        @endif
                    </div>
                @else
                    <div class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <p class="text-3xl font-black text-lime-300 md:text-4xl">{{ $account->formattedPrice() }}</p>
                        @if ($account->hasDiscount())
                            <span class="text-sm text-slate-500 line-through">{{ $account->formattedOriginalPrice() }}</span>
                            <span class="rounded-md bg-rose-400/15 px-2 py-0.5 text-xs font-black text-rose-300">-{{ $account->discountPercent() }}%</span>
                        @endif
                    </div>
                    @if ($account->hasDiscount())
                        <p class="mt-1 text-xs font-semibold text-emerald-300">Hemat {{ $account->formattedSavings() }}</p>
                    @endif
                @endif

                @if ($account->description)
                    <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-300">{{ $account->description }}</p>
                @endif

                @if (! $account->is_rental && $account->status === 'available' && $account->isOnFlashSale())
                    @php($left = max(0, now()->diffInSeconds($account->flash_sale_ends_at, false)))
                    <div class="flash-box mt-5 flex items-center gap-3 rounded-2xl border p-3 md:p-4" data-countdown-parts="{{ $account->flash_sale_ends_at->toIso8601ZuluString() }}" aria-label="Flash sale">
                        <span class="hidden size-10 shrink-0 items-center justify-center rounded-full bg-rose-400/15 text-rose-300 sm:flex">
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M13 2 4.5 13.5H11L10 22l8.5-11.5H12L13 2Z" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-rose-300 sm:text-sm">Flash sale <span class="size-1.5 animate-pulse rounded-full bg-rose-400"></span></p>
                            <p class="truncate text-[11px] text-slate-400 md:text-xs">Penawaran terbatas, segera checkout!</p>
                        </div>
                        <div class="flex items-start gap-1 md:gap-1.5" role="timer" aria-label="Sisa waktu flash sale">
                            <div class="text-center" data-cd-days @if ($left < 86400) hidden @endif><span class="flash-cell" data-cd="d">{{ intdiv($left, 86400) }}</span><small class="flash-unit">Hari</small></div>
                            <div class="text-center"><span class="flash-cell" data-cd="h">{{ sprintf('%02d', intdiv($left % 86400, 3600)) }}</span><small class="flash-unit">Jam</small></div>
                            <span class="pt-1.5 text-xs font-bold text-rose-300/70">:</span>
                            <div class="text-center"><span class="flash-cell" data-cd="m">{{ sprintf('%02d', intdiv($left % 3600, 60)) }}</span><small class="flash-unit">Menit</small></div>
                            <span class="pt-1.5 text-xs font-bold text-rose-300/70">:</span>
                            <div class="text-center"><span class="flash-cell" data-cd="s">{{ sprintf('%02d', $left % 60) }}</span><small class="flash-unit">Detik</small></div>
                        </div>
                    </div>
                @endif

                <div class="mt-5">
                    <h2 class="mb-2 text-sm font-bold">Spesifikasi akun</h2>
                    <div class="whitespace-pre-line rounded-xl bg-white/5 p-4 text-sm leading-7 text-slate-300">{{ $account->specifications }}</div>
                </div>

                @if ($account->owner)
                    <div class="mt-5 inline-flex max-w-full items-center gap-3 rounded-2xl border border-white/10 bg-white/5 py-2.5 pl-2.5 pr-4">
                        @include('layouts.avatar', ['user' => $account->owner, 'class' => 'size-10 shrink-0 rounded-full text-sm'])
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Admin penjual</p>
                            <p class="truncate text-sm font-black">{{ $account->owner->name }}</p>
                        </div>
                        <svg class="ml-2 size-5 shrink-0 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Admin resmi" role="img"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM9 12l2 2 4-4" /></svg>
                    </div>
                @endif

                @unless ($account->is_rental)
                    {{-- The super admin's accounts are paid on the website (Tripay); the rest through WhatsApp. --}}
                    @php($gateway = $account->status === 'available' && $account->usesPaymentGateway())
                    @php($holder = $gateway ? $account->pendingOrder() : null)
                    <div class="mt-5 space-y-3">
                        @if ($url = $account->whatsappUrl())
                            <div class="grid grid-cols-2 gap-3">
                                {{-- "Tanya Stok" opens the website's live chat with the question typed in (guests are asked to sign in there). --}}
                                <button type="button" class="btn-secondary w-full" data-chat-ask="{{ 'Halo admin, apakah akun '.$account->title.' ('.$account->formattedPrice().') masih tersedia? '.route('accounts.show', $account->slug) }}">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" /></svg>Tanya Stok</button>
                                <a class="btn-wa w-full" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z" /></svg>Chat admin WA</a>
                            </div>
                            <div class="grid gap-3">
                                @if ($gateway)
                                    @if ($holder && $holder->user_id !== auth()->id())
                                        <span class="btn btn-buy w-full cursor-not-allowed opacity-60" title="Akun ini sedang dibayar pembeli lain">Sedang dibayar pembeli lain</span>
                                    @else
                                        <a class="btn btn-buy w-full" href="{{ $holder ? route('orders.show', $holder) : route('checkout', ['akun' => [$account->id]]) }}" data-checkout-button>{{ $holder ? 'Lanjutkan pembayaran' : 'Beli Sekarang' }}<span class="hidden sm:inline"> — {{ $account->formattedPrice() }}</span></a>
                                    @endif
                                @else
                                    <a class="btn btn-buy w-full" href="{{ $account->buyWhatsappUrl() }}" target="_blank" rel="noopener noreferrer">Beli Sekarang<span class="hidden sm:inline"> — {{ $account->formattedPrice() }}</span></a>
                                @endif
                            </div>
                            @if ($gateway)
                                <p class="flex items-center justify-center gap-2 text-center text-xs text-slate-400">
                                    <svg class="size-4 shrink-0 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM9 12l2 2 4-4" /></svg>
                                    Bayar aman di website: QRIS, virtual account, e-wallet
                                </p>
                            @endif
                            @if ($installmentUrl = $account->installmentInquiryWhatsappUrl())
                                <a href="{{ $installmentUrl }}" target="_blank" rel="noopener noreferrer" data-installment-ask
                                    class="flex items-center gap-3 rounded-xl border border-amber-300/30 bg-amber-300/5 px-4 py-3 text-sm transition hover:border-amber-300/60 hover:bg-amber-300/10">
                                    <svg class="size-5 shrink-0 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20M6 15h4" /></svg>
                                    <span class="min-w-0 flex-1"><span class="block font-bold text-amber-300">Mau cicil?</span><span class="block text-xs text-slate-300">Tanya syarat cicilan langsung ke admin lewat WhatsApp.</span></span>
                                    <svg class="size-5 shrink-0 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z" /></svg>
                                </a>
                            @endif
                        @elseif ($account->status === 'sold' || $account->isBooked())
                            <a class="btn w-full" href="{{ route('catalog.all') }}">Lihat akun lain ↗</a>
                        @else
                            <p class="rounded-xl bg-white/5 p-4 text-center text-sm text-slate-400">Kontak WhatsApp belum diatur oleh admin.</p>
                        @endif
                    </div>
                @endunless

                {{-- Dilihat / Suka --}}
                <dl class="mt-5 grid grid-cols-2 gap-3">
                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-violet-400/15 text-violet-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg></span>
                        <div><dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Dilihat</dt><dd class="text-lg font-black leading-tight">{{ \App\Models\GameAccount::compactCount($account->view_count) }}</dd></div>
                    </div>
                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-rose-400/15 text-rose-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg></span>
                        <div><dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Suka</dt><dd class="text-lg font-black leading-tight" data-like-count="{{ $account->id }}">{{ \App\Models\GameAccount::compactCount($account->like_count) }}</dd></div>
                    </div>
                </dl>
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

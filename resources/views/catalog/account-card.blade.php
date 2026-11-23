<article
    class="panel group relative flex flex-col overflow-hidden transition duration-200 hover:-translate-y-1 hover:border-lime-300/40">
    <a href="{{ route('accounts.show', $account->slug) }}" class="block">
        <div class="catalog-media"><img src="{{ $account->imageUrl() }}" alt="{{ $account->title }}"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105 {{ $account->status === 'sold' ? 'grayscale' : '' }}"
                loading="lazy">
            @if ($account->status === 'sold')
                <span class="absolute inset-0 z-10 flex items-center justify-center bg-[#070b1c]/45"><span
                        class="-rotate-12 rounded-lg border-2 border-rose-300 bg-[#070b1c]/70 px-3 py-1 text-base font-black uppercase tracking-widest text-rose-300 md:text-2xl">Terjual</span></span>
            @elseif ($account->isBooked())
                <span class="absolute inset-0 z-10 flex items-center justify-center bg-[#070b1c]/45"><span
                        class="-rotate-12 rounded-lg border-2 bg-[#070b1c]/70 px-3 py-1 text-base font-black uppercase tracking-widest md:text-2xl border-amber-300 text-amber-300">{{ $account->publicStatusLabel() }}</span></span>
            @else
                <span class="status status-live absolute left-2 top-2 z-10 text-lime-300 md:left-3 md:top-3">@include('catalog.live-dot')Tersedia</span>
            @endif
            @if ($account->isOnFlashSale())
                <span class="absolute bottom-2 left-2 z-10 rounded-lg bg-amber-300 px-2 py-1 text-[10px] font-black text-slate-950 md:text-xs">⚡ <span
                        data-countdown="{{ $account->flash_sale_ends_at->toIso8601ZuluString() }}">{{ $account->flashSaleCountdown() }}</span></span>
            @endif
            @if (str_starts_with($account->main_image, 'demo/'))
                <span class="absolute bottom-2 right-3 z-10 text-[10px] text-white/60">DATA DEMO</span>
            @endif
        </div>
        <div class="p-3 md:p-4">
            <div class="flex flex-wrap items-center gap-1"><span class="inline-block max-w-full truncate rounded-md border border-amber-300/40 bg-amber-300/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-amber-300">{{ $account->category->name }}</span>@include('catalog.card-badges')</div>
            <h3 class="mt-2 line-clamp-2 text-sm font-black leading-5 md:text-base md:leading-6">{{ $account->title }}</h3>
            <div class="mt-2">
                @if ($account->status === 'sold')
                    <p class="text-sm font-black text-rose-300 md:text-lg">Sudah terjual</p>
                @elseif ($account->isBooked())
                    <p class="text-base font-black text-slate-400 md:text-xl">{{ $account->formattedPrice() }}</p>
                    <p class="mt-1 text-[11px] font-bold text-amber-300 md:text-xs">Sudah dipesan pembeli</p>
                @else
                    @if ($account->hasDiscount())
                        <p class="text-[11px] font-semibold text-slate-500 line-through md:text-xs">{{ $account->formattedOriginalPrice() }}</p>
                    @endif
                    <p class="text-base font-black text-lime-300 md:text-xl">{{ $account->formattedPrice() }}</p>
                    @if ($account->hasDiscount())
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <span class="rounded-md bg-emerald-300/15 px-2 py-0.5 text-[10px] font-bold text-emerald-300 md:text-xs">Hemat {{ $account->formattedSavings() }}</span>
                            <span class="rounded-md bg-rose-400 px-1.5 py-0.5 text-[10px] font-black text-slate-950 md:text-xs">-{{ $account->discountPercent() }}%</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </a>
    {{-- Share / Suka / Keranjang. Labels sit under the icon on narrow cards and beside it on wide ones. --}}
    <div class="@container mt-auto px-2 pb-2 md:px-3 md:pb-3">
        <div class="grid grid-cols-[1fr_1fr_1.35fr] gap-1 @[15rem]:gap-2">
            <button type="button" class="card-action" data-share-url="{{ route('accounts.show', $account->slug) }}" data-share-title="{{ $account->title }}" aria-label="Bagikan {{ $account->title }}">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4" /></svg>
                <span data-share-label>Share</span>
            </button>
            @include('catalog.wishlist-button', ['account' => $account, 'variant' => 'card'])
            @if ($account->status === 'available' && ! $account->is_rental)
                <button type="button" class="card-action hover:text-lime-300 aria-pressed:border-lime-300/50 aria-pressed:text-lime-300" data-cart-toggle="{{ $account->id }}" data-account-title="{{ $account->title }}"
                    aria-pressed="false" aria-label="Tambahkan {{ $account->title }} ke keranjang" disabled>
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1.5" /><circle cx="18" cy="20" r="1.5" /><path d="M2.5 3h2.6l2.4 12h11l2-8H6.3" /></svg>
                    <span>Keranjang</span>
                </button>
            @else
                <span class="card-action cursor-not-allowed opacity-45" title="Akun ini tidak bisa dimasukkan ke keranjang">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1.5" /><circle cx="18" cy="20" r="1.5" /><path d="M2.5 3h2.6l2.4 12h11l2-8H6.3" /></svg>
                    <span>Keranjang</span>
                </span>
            @endif
        </div>
    </div>
</article>

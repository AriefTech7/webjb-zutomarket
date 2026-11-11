<article
    class="panel group relative overflow-hidden transition duration-200 hover:-translate-y-1 hover:border-lime-300/40">
    @include('catalog.wishlist-button', ['account' => $account, 'compact' => true])
    <a href="{{ route('accounts.show', $account->slug) }}" class="block">
        <div class="catalog-media"><img src="{{ $account->imageUrl() }}" alt="{{ $account->title }}"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105 {{ $account->status === 'sold' ? 'grayscale' : '' }}"
                loading="lazy">
            @if ($account->status === 'sold')
                <span class="absolute inset-0 z-10 flex items-center justify-center bg-[#070b1c]/45"><span
                        class="-rotate-12 rounded-lg border-2 border-rose-300 bg-[#070b1c]/70 px-3 py-1 text-base font-black uppercase tracking-widest text-rose-300 md:text-2xl">Terjual</span></span>
            @else
                <span class="status absolute left-2 top-2 z-10 text-lime-300 md:left-3 md:top-3">● Tersedia</span>
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
            <span class="inline-block max-w-full truncate rounded-md border border-amber-300/40 bg-amber-300/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-amber-300">{{ $account->category->name }}</span>
            <h3 class="mt-2 line-clamp-2 text-sm font-black leading-5 md:text-base md:leading-6">{{ $account->title }}</h3>
            <div class="mt-2">
                @if ($account->status === 'sold')
                    <p class="text-sm font-black text-rose-300 md:text-lg">Sudah terjual</p>
                @else
                    @if ($account->hasDiscount())
                        <p class="text-[11px] font-semibold text-slate-500 line-through md:text-xs">{{ $account->formattedOriginalPrice() }}</p>
                    @endif
                    <p class="text-base font-black text-lime-300 md:text-xl">{{ $account->formattedPrice() }}</p>
                    @if ($account->hasDiscount())
                        <span class="mt-1 inline-block rounded-md bg-emerald-300/15 px-2 py-0.5 text-[10px] font-bold text-emerald-300 md:text-xs">Hemat {{ $account->formattedSavings() }}</span>
                    @endif
                @endif
            </div>
        </div>
    </a>
</article>

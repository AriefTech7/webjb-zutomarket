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
        <div class="p-3 md:p-5">
            <p class="mb-1 truncate text-[10px] font-bold uppercase tracking-widest text-lime-300 md:mb-2">{{ $account->category->name }}
            </p>
            <h3 class="line-clamp-2 min-h-10 text-sm font-bold leading-5 md:line-clamp-none md:min-h-12 md:text-base md:leading-6">{{ $account->title }}</h3>
            <p class="muted mt-2 hidden text-xs md:line-clamp-2">{{ $account->specifications }}</p>
            <div class="mt-2 flex items-center justify-between border-t border-white/10 pt-2 md:mt-5 md:pt-4">
                <div>
                    @if ($account->status === 'sold')
                        <p class="mb-1 hidden text-[10px] text-slate-500 md:block">Status</p>
                        <p class="text-sm font-bold text-rose-300 md:text-base">Sudah terjual</p>
                    @else
                        @if ($account->hasDiscount())
                            <p class="text-[10px] text-slate-500 line-through md:text-xs">{{ $account->formattedOriginalPrice() }}</p>
                        @else
                            <p class="mb-1 hidden text-[10px] text-slate-500 md:block">Harga akun</p>
                        @endif
                        <p class="flex flex-wrap items-center gap-1.5 text-sm font-bold text-slate-100 md:text-base">{{ $account->formattedPrice() }}
                            @if ($account->hasDiscount())
                                <span class="rounded bg-rose-400/15 px-1.5 text-[10px] font-bold text-rose-300">-{{ $account->discountPercent() }}%</span>
                            @endif
                        </p>
                    @endif
                </div><span
                    class="hidden h-10 w-10 items-center justify-center rounded-xl md:flex border border-white/10 bg-white/5 text-lime-300 transition group-hover:border-lime-300 group-hover:bg-lime-300 group-hover:text-black"
                    aria-hidden="true">↗</span>
            </div>
        </div>
    </a>
</article>

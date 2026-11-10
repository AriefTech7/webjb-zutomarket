@php($cheapest = $account->cheapestRentalPackage())
<article class="panel group relative overflow-hidden transition duration-200 hover:-translate-y-1 hover:border-lime-300/40">
    <a href="{{ route('accounts.show', $account->slug) }}" class="block">
        <div class="catalog-media"><img src="{{ $account->imageUrl() }}" alt="{{ $account->title }}"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
            <span class="status absolute left-2 top-2 z-10 md:left-3 md:top-3 {{ $account->status === 'available' ? 'text-lime-300' : 'text-amber-300' }}">{{ $account->status === 'available' ? '● Siap disewa' : 'Sedang disewa' }}</span>
        </div>
        <div class="p-3 md:p-5">
            <p class="mb-1 truncate text-[10px] font-bold uppercase tracking-widest text-lime-300 md:mb-2">{{ $account->category->name }}</p>
            <h3 class="line-clamp-2 min-h-10 text-sm font-bold leading-5 md:min-h-12 md:text-base md:leading-6">{{ $account->title }}</h3>
            @if ($account->status === 'rented' && $account->ready_estimate_at)
                <p class="mt-1 text-[10px] font-semibold text-amber-300 md:text-xs">Ready ± {{ $account->ready_estimate_at->timezone(\App\Models\GameAccount::LOCAL_TIMEZONE)->format('d M H:i') }} WIB</p>
            @endif
            <div class="mt-2 border-t border-white/10 pt-2 md:mt-5 md:pt-4">
                @if ($cheapest)
                    <p class="text-[10px] text-slate-500">Mulai</p>
                    <p class="text-sm font-bold text-slate-100 md:text-base">{{ $cheapest->formattedPrice() }} <span class="text-xs font-normal text-slate-400">/ {{ $cheapest->duration }}</span></p>
                @endif
            </div>
        </div>
    </a>
</article>

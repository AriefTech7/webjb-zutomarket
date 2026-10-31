<article
    class="panel group relative overflow-hidden transition duration-200 hover:-translate-y-1 hover:border-lime-300/40">
    <a href="{{ route('accounts.show', $account->slug) }}" class="block">
        <div class="catalog-media"><img src="{{ $account->imageUrl() }}" alt="{{ $account->title }}"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105 {{ $account->status === 'sold' ? 'grayscale' : '' }}"
                loading="lazy"><span
                class="status absolute left-3 top-3 z-10 {{ $account->status === 'available' ? 'text-lime-300' : 'text-slate-300' }}">{{ $account->status === 'available' ? '● Tersedia' : 'Terjual' }}</span>
            @if (str_starts_with($account->main_image, 'demo/'))
                <span class="absolute bottom-2 right-3 z-10 text-[10px] text-white/60">DATA DEMO</span>
            @endif
        </div>
        <div class="p-5">
            <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-lime-300">{{ $account->category->name }}
            </p>
            <h3 class="min-h-12 text-base font-bold leading-6">{{ $account->title }}</h3>
            <p class="muted mt-2 line-clamp-2 text-xs">{{ $account->specifications }}</p>
            <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4">
                <div>
                    <p class="mb-1 text-[10px] text-slate-500">Harga akun</p>
                    <p class="font-bold text-slate-100">{{ $account->formattedPrice() }}</p>
                </div><span
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-lime-300 transition group-hover:border-lime-300 group-hover:bg-lime-300 group-hover:text-black"
                    aria-hidden="true">↗</span>
            </div>
        </div>
    </a>
</article>

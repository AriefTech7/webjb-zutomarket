@if ($accounts->isNotEmpty())
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <ul class="space-y-3">
            @foreach ($accounts as $account)
                <li class="panel flex items-center gap-3 p-3 md:gap-4 md:p-4">
                    <a href="{{ route('accounts.show', $account->slug) }}" class="shrink-0">
                        <img src="{{ $account->imageUrl() }}" alt="" class="h-20 w-16 rounded-xl object-cover object-top md:h-24 md:w-20" loading="lazy">
                    </a>
                    <div class="min-w-0 flex-1">
                        <span class="inline-block max-w-full truncate rounded-md border border-amber-300/40 bg-amber-300/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-amber-300">{{ $account->category->name }}</span>
                        <a href="{{ route('accounts.show', $account->slug) }}" class="mt-1 block truncate text-sm font-black hover:text-lime-300 md:text-base">{{ $account->title }}</a>
                        <p class="mt-1 text-[11px] font-semibold {{ $gateway->contains($account) ? 'text-emerald-300' : 'text-slate-400' }}">{{ $gateway->contains($account) ? 'Bayar di website' : 'Beli lewat WhatsApp admin/penjual' }}</p>
                        <p class="mt-1 text-base font-black text-lime-300 md:text-lg">{{ $account->formattedPrice() }}
                            @if ($account->hasDiscount())<span class="ml-1 text-xs font-semibold text-slate-500 line-through">{{ $account->formattedOriginalPrice() }}</span>@endif
                        </p>
                    </div>
                    <button type="button" data-cart-toggle="{{ $account->id }}" data-account-title="{{ $account->title }}" aria-pressed="true"
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-white/10 text-slate-400 transition hover:border-rose-300/50 hover:text-rose-300" aria-label="Hapus {{ $account->title }} dari keranjang">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6" /></svg>
                    </button>
                </li>
            @endforeach
        </ul>
        <aside class="panel p-5 lg:sticky lg:top-28" aria-labelledby="cart-summary">
            <h2 id="cart-summary" class="text-lg font-black">Ringkasan</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-slate-400">Jumlah akun</dt><dd class="font-bold">{{ $accounts->count() }}</dd></div>
                <div class="flex justify-between gap-3 border-t border-white/10 pt-2"><dt class="text-slate-400">Total harga</dt><dd class="text-xl font-black text-lime-300">Rp {{ number_format($accounts->sum('price'), 0, ',', '.') }}</dd></div>
            </dl>
            <div class="mt-5 space-y-2">
                @if ($gateway->isNotEmpty())
                    <a class="btn btn-buy w-full" href="{{ route('checkout', ['akun' => $gateway->modelKeys()]) }}" data-checkout-button>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="10" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
                        Bayar di website{{ $checkouts->isNotEmpty() || $gateway->count() < $accounts->count() ? ' ('.$gateway->count().' akun · Rp '.number_format($gateway->sum('price'), 0, ',', '.').')' : '' }}
                    </a>
                    <p class="muted text-center text-xs">QRIS, virtual account, e-wallet</p>
                @endif
                @foreach ($checkouts as $checkout)
                    @if ($checkout['url'])
                        <a class="btn w-full" href="{{ $checkout['url'] }}" target="_blank" rel="noopener noreferrer">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" /></svg>
                            Checkout via WhatsApp{{ $checkouts->count() > 1 ? ' ('.$checkout['count'].' akun · Rp '.number_format($checkout['total'], 0, ',', '.').')' : '' }}
                        </a>
                    @else
                        <p class="rounded-xl bg-white/5 p-3 text-center text-sm text-slate-400">Kontak WhatsApp belum diatur oleh admin.</p>
                    @endif
                @endforeach
            </div>
            @if ($checkouts->count() > 1)
                <p class="muted mt-3 text-xs">Akun di keranjangmu ditangani {{ $checkouts->count() }} penjual berbeda, jadi checkout dikirim per penjual.</p>
            @endif
        </aside>
    </div>
@endif

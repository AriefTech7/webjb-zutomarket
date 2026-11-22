@extends('layouts.app')
@section('title', 'Checkout')
@section('content')
    @php
        $subtotal = (int) $accounts->sum('price');
        $oldMethod = old('method');
    @endphp
    <section class="shell max-w-5xl py-8 md:py-12">
        <nav class="muted text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Beranda</a> / <a class="hover:text-white" href="{{ route('cart') }}">Keranjang</a> / <span class="text-slate-300">Checkout</span></nav>
        <h1 class="mt-3 text-3xl font-black md:text-4xl">Checkout<span class="text-lime-300">.</span></h1>
        <p class="muted mt-2">Bayar langsung di website dengan QRIS, virtual account bank, atau e-wallet. Status pembayaran diperbarui otomatis.</p>

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start" data-checkout-form data-subtotal="{{ $subtotal }}">
            @csrf
            @foreach ($accounts as $account)
                <input type="hidden" name="akun[]" value="{{ $account->id }}">
            @endforeach

            <div class="min-w-0 space-y-5">
                <div class="panel p-5 md:p-6">
                    <h2 class="text-lg font-black">1. Akun yang dibeli</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($accounts as $account)
                            <li class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                                <img src="{{ $account->imageUrl() }}" alt="" class="h-16 w-14 shrink-0 rounded-lg object-cover object-top" loading="lazy">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-black uppercase tracking-wide text-amber-300">{{ $account->category->name }}</p>
                                    <a class="block truncate font-black hover:text-lime-300" href="{{ route('accounts.show', $account->slug) }}">{{ $account->title }}</a>
                                </div>
                                <p class="shrink-0 font-black text-lime-300">{{ $account->formattedPrice() }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="panel p-5 md:p-6">
                    <h2 class="text-lg font-black">2. Data pembeli</h2>
                    <p class="muted mt-1 text-sm">Dipakai untuk bukti pembayaran dan serah terima data akun.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div><label class="label" for="customer_name">Nama</label><input class="field" id="customer_name" name="customer_name" value="{{ old('customer_name', $user->name) }}" maxlength="60" required></div>
                        <div><label class="label" for="customer_phone">Nomor WhatsApp</label><input class="field" id="customer_phone" name="customer_phone" type="tel" inputmode="tel" value="{{ old('customer_phone', $user->whatsapp) }}" maxlength="20" required placeholder="08xxxxxxxxxx"></div>
                        <div class="sm:col-span-2"><label class="label" for="customer_email">Email</label><input class="field opacity-70" id="customer_email" value="{{ $user->email }}" disabled><p class="muted mt-1 text-xs">Bukti pembayaran dikirim ke email akunmu.</p></div>
                    </div>
                </div>

                <div class="panel p-5 md:p-6">
                    <h2 class="text-lg font-black">3. Metode pembayaran</h2>
                    @if ($channels === null)
                        <p class="mt-4 rounded-xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-200">Metode pembayaran sedang tidak bisa dimuat. Muat ulang halaman ini beberapa saat lagi.</p>
                    @elseif ($channels === [])
                        <p class="mt-4 rounded-xl bg-white/5 p-4 text-sm text-slate-300">Belum ada metode pembayaran untuk total ini. Hubungi admin.</p>
                    @else
                        @foreach (collect($channels)->groupBy('group') as $group => $groupChannels)
                            <p class="mt-5 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $group }}</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                @foreach ($groupChannels as $channel)
                                    <label class="pay-option flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition">
                                        <input class="sr-only" type="radio" name="method" value="{{ $channel['code'] }}" data-fee="{{ $channel['fee'] }}" data-name="{{ $channel['name'] }}" @checked($oldMethod === $channel['code']) required>
                                        <span class="flex h-9 w-14 shrink-0 items-center justify-center rounded-lg bg-white p-1">@if ($channel['icon'])<img src="{{ $channel['icon'] }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy">@endif</span>
                                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold">{{ $channel['name'] }}</span><span class="block text-xs text-slate-400">{{ $channel['fee'] > 0 ? 'Biaya '.\App\Models\Order::rupiah($channel['fee']) : 'Tanpa biaya' }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <aside class="panel p-5 md:p-6 lg:sticky lg:top-28" aria-labelledby="checkout-summary">
                <h2 id="checkout-summary" class="text-lg font-black">Ringkasan pembayaran</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-400">Harga akun ({{ $accounts->count() }})</dt><dd class="font-bold">{{ \App\Models\Order::rupiah($subtotal) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-400">Biaya <span data-checkout-method>pembayaran</span></dt><dd class="font-bold" data-checkout-fee>–</dd></div>
                    <div class="flex items-baseline justify-between gap-3 border-t border-white/10 pt-3"><dt class="font-bold">Total bayar</dt><dd class="text-2xl font-black text-lime-300" data-checkout-total>{{ \App\Models\Order::rupiah($subtotal) }}</dd></div>
                </dl>
                <label class="mt-5 flex items-start gap-2 text-xs leading-5 text-slate-300">
                    <input type="checkbox" name="agree" value="1" class="mt-0.5 size-4 shrink-0 accent-lime-300" @checked(old('agree')) required>
                    <span>Saya setuju dengan <a class="font-bold text-lime-300 hover:underline" href="{{ route('terms') }}" target="_blank">Ketentuan Layanan</a> dan <a class="font-bold text-lime-300 hover:underline" href="{{ route('refund') }}" target="_blank">Kebijakan Refund</a>.</span>
                </label>
                <button class="btn btn-buy mt-4 w-full" @disabled(! $channels)>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="11" width="18" height="10" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
                    Bayar Sekarang
                </button>
                <p class="muted mt-3 text-xs leading-5">Akun ditahan untukmu selama {{ (int) config('services.tripay.expiry_minutes') }} menit sampai pembayaran selesai. Pembayaran diproses oleh Tripay{{ \App\Services\Tripay::sandbox() ? ' (mode uji coba / sandbox)' : '' }}.</p>
            </aside>
        </form>
    </section>
@endsection

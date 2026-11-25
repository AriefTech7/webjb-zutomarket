@extends('layouts.app')
@section('title', 'Pesanan '.$order->merchant_ref)
@section('content')
    @php
        $statusClass = ['unpaid' => 'border-amber-300/40 bg-amber-300/10 text-amber-300', 'paid' => 'border-emerald-300/40 bg-emerald-300/10 text-emerald-300', 'refunded' => 'border-violet-300/40 bg-violet-300/10 text-violet-300'][$order->status] ?? 'border-rose-400/40 bg-rose-400/10 text-rose-300';
        $pending = $order->isPending();
    @endphp
    <section class="shell max-w-3xl py-8 md:py-12" @if ($pending) data-order-poll="{{ route('orders.status', $order) }}" data-order-status="{{ $order->status }}" @endif>
        <nav class="muted text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Beranda</a> / <a class="hover:text-white" href="{{ route('orders') }}">Pesanan saya</a> / <span class="text-slate-300">{{ $order->merchant_ref }}</span></nav>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-black md:text-3xl">Pesanan {{ $order->merchant_ref }}</h1>
            <span class="rounded-full border px-3 py-1 text-xs font-black uppercase tracking-wider {{ $statusClass }}">{{ $order->statusLabel() }}</span>
        </div>

        @if ($order->status === 'paid')
            <div class="panel mt-6 border-emerald-300/30 p-5 md:p-6">
                <p class="text-lg font-black text-emerald-300">Pembayaran berhasil 🎉</p>
                <p class="mt-2 text-sm leading-6 text-slate-300">Terima kasih! Pembayaran {{ \App\Models\Order::rupiah($order->total) }} sudah kami terima {{ $order->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB.
                    @if ($order->isJoki())
                        Admin akan menghubungimu lewat <strong class="text-white">live chat</strong> untuk meminta data login dan memulai joki. Jangan login ke akun selama proses joki berjalan.
                    @elseif ($order->delivered_at)
                        Data akun sudah diserahkan admin.
                    @else
                        Admin akan segera menyerahkan data login akun melalui <strong class="text-white">live chat</strong> atau WhatsApp ke nomor {{ $order->customer_phone }}. Setelah menerima, langsung ganti password dan amankan akunnya.
                    @endif
                </p>
                <button type="button" class="btn mt-4" data-open-chat>Buka live chat admin</button>
            </div>
        @elseif ($pending)
            <div class="panel mt-6 p-5 md:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total bayar · {{ $order->payment_name }}</p>
                        <p class="mt-1 text-3xl font-black text-lime-300">{{ \App\Models\Order::rupiah($order->total) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Bayar sebelum</p>
                        <p class="mt-1 text-xl font-black text-rose-300" data-countdown="{{ $order->expires_at->toIso8601ZuluString() }}">{{ $order->expires_at->timezone('Asia/Jakarta')->format('H:i') }}</p>
                        <p class="muted text-xs">{{ $order->expires_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
                    </div>
                </div>

                @if ($order->qr_url)
                    <div class="mt-5 flex flex-col items-center rounded-2xl bg-white p-4">
                        <img src="{{ $order->qr_url }}" alt="Kode QR pembayaran {{ $order->payment_name }}" class="size-60 max-w-full">
                        <p class="mt-2 text-center text-xs font-semibold text-slate-950">Scan dengan aplikasi bank / e-wallet yang mendukung QRIS</p>
                    </div>
                @endif
                @if ($order->pay_code)
                    <div class="mt-5 rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ str_contains(strtoupper($order->payment_method), 'VA') ? 'Nomor virtual account' : 'Kode pembayaran' }}</p>
                        <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                            <p class="break-all font-mono text-2xl font-black tracking-wider">{{ $order->pay_code }}</p>
                            <button type="button" class="btn-secondary" data-copy="{{ $order->pay_code }}">Salin</button>
                        </div>
                    </div>
                @endif
                @if ($order->pay_url)
                    <a class="btn-secondary mt-4 w-full" href="{{ $order->pay_url }}" target="_blank" rel="noopener noreferrer">Buka halaman pembayaran Tripay ↗</a>
                @endif
                <p class="muted mt-4 flex items-center gap-2 text-xs"><span class="size-2 animate-pulse rounded-full bg-amber-300"></span>Menunggu pembayaran. Halaman ini diperbarui otomatis setelah kamu membayar.</p>
            </div>

            @if ($order->instructions)
                <div class="panel mt-5 p-5 md:p-6">
                    <h2 class="text-lg font-black">Cara pembayaran</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($order->instructions as $instruction)
                            <details class="rounded-xl bg-white/5 p-4" @if ($loop->first) open @endif>
                                <summary class="cursor-pointer font-bold">{{ $instruction['title'] ?? 'Langkah pembayaran' }}</summary>
                                <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm leading-6 text-slate-300">
                                    @foreach ($instruction['steps'] ?? [] as $step)
                                        <li>{{ html_entity_decode(strip_tags((string) $step)) }}</li>
                                    @endforeach
                                </ol>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="panel mt-6 p-5 md:p-6">
                <p class="text-lg font-black text-rose-300">{{ $order->status === 'refunded' ? 'Pembayaran sudah dikembalikan.' : 'Pembayaran tidak selesai.' }}</p>
                <p class="muted mt-2 text-sm">{{ $order->status === 'refunded' ? 'Dana pesanan ini sudah dikembalikan sesuai Kebijakan Refund.' : 'Waktu pembayaran habis atau pembayaran gagal, jadi akun dilepas kembali. Kalau akunnya masih tersedia, kamu bisa checkout lagi.' }}</p>
                <a class="btn mt-4" href="{{ route('catalog.all') }}">Lihat akun lain ↗</a>
            </div>
        @endif

        <div class="panel mt-5 p-5 md:p-6">
            <h2 class="text-lg font-black">Rincian pesanan</h2>
            <ul class="mt-3 divide-y divide-white/10 text-sm">
                @foreach ($order->items as $item)
                    <li class="py-2">
                        <div class="flex justify-between gap-3">
                            @if ($item->account)<a class="min-w-0 truncate font-semibold hover:text-lime-300" href="{{ route('accounts.show', $item->account->slug) }}">{{ $item->title }}</a>@else<span class="min-w-0 font-semibold">{{ $item->title }}</span>@endif
                            <span class="shrink-0 font-bold">{{ \App\Models\Order::rupiah($item->price) }}</span>
                        </div>
                        @if ($item->details)
                            <dl class="mt-2 grid gap-1 text-xs">
                                @foreach ($item->details as $detailLabel => $detailValue)
                                    <div class="flex gap-2"><dt class="w-36 shrink-0 text-slate-400">{{ $detailLabel }}</dt><dd class="min-w-0 break-words text-slate-200">{{ $detailValue }}</dd></div>
                                @endforeach
                            </dl>
                        @endif
                    </li>
                @endforeach
            </ul>
            <dl class="mt-2 space-y-1 border-t border-white/10 pt-3 text-sm">
                @if ($order->discount)
                    <div class="flex justify-between gap-3 text-emerald-300"><dt>Voucher {{ $order->voucher_code }}</dt><dd>−{{ \App\Models\Order::rupiah($order->discount) }}</dd></div>
                @endif
                <div class="flex justify-between gap-3"><dt class="text-slate-400">Biaya {{ $order->payment_name }}</dt><dd>{{ \App\Models\Order::rupiah($order->fee) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="font-bold">Total</dt><dd class="font-black text-lime-300">{{ \App\Models\Order::rupiah($order->total) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-400">Pembeli</dt><dd class="text-right">{{ $order->customer_name }} · {{ $order->customer_phone }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-400">Dibuat</dt><dd>{{ $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</dd></div>
            </dl>
        </div>
    </section>
@endsection

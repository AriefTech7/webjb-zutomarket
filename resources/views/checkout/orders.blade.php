@extends('layouts.app')
@section('title', 'Pesanan saya')
@section('content')
    <section class="shell max-w-3xl py-8 md:py-12">
        <h1 class="text-3xl font-black md:text-4xl">Pesanan saya<span class="text-lime-300">.</span></h1>
        <p class="muted mt-2">Pembelian yang kamu bayar di website.</p>
        <div class="mt-6 space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="panel flex items-center justify-between gap-4 p-4 transition hover:border-lime-300/40">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-400">{{ $order->merchant_ref }} · {{ $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</p>
                        <p class="mt-1 truncate font-black">{{ $order->items->pluck('title')->implode(', ') }}</p>
                        <p class="text-sm font-bold text-lime-300">{{ \App\Models\Order::rupiah($order->total) }}</p>
                    </div>
                    <span @class(['shrink-0 rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-wider', 'border-amber-300/40 text-amber-300' => $order->status === 'unpaid', 'border-emerald-300/40 text-emerald-300' => $order->status === 'paid', 'border-rose-400/40 text-rose-300' => ! in_array($order->status, ['unpaid', 'paid'], true)])>{{ $order->statusLabel() }}</span>
                </a>
            @empty
                <div class="panel p-10 text-center">
                    <p class="font-bold">Belum ada pesanan.</p>
                    <a class="btn mt-4" href="{{ route('catalog.all') }}">Mulai belanja ↗</a>
                </div>
            @endforelse
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    </section>
@endsection

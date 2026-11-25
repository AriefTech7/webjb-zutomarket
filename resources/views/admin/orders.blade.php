@extends('layouts.admin')
@section('title', 'Pesanan website')
@section('admin-title', 'Pesanan website')
@section('admin-content')
    {{-- Store-wide switch: website checkout (Tripay) on or off. --}}
    @php($gatewayKeys = \App\Services\Tripay::enabled())
    @php($gatewayOn = \App\Services\Tripay::active())
    <section class="panel mb-5 flex flex-wrap items-center justify-between gap-4 p-5" aria-labelledby="gateway-title" data-gateway-switch>
        <div class="min-w-0">
            <h2 id="gateway-title" class="flex flex-wrap items-center gap-2 font-black">Gunakan payment gateway
                <span @class(['rounded-full border px-2.5 py-0.5 text-[11px] font-black uppercase tracking-wider', 'border-emerald-300/40 text-emerald-300' => $gatewayOn, 'border-rose-400/40 text-rose-300' => ! $gatewayOn])>{{ $gatewayOn ? 'ON' : 'OFF' }}</span>
            </h2>
            <p class="muted mt-1 text-sm">
                @if (! $gatewayKeys)
                    Key Tripay belum diisi di server, jadi semua pembelian lewat WhatsApp.
                @elseif ($gatewayOn)
                    Akun milik super admin dibayar langsung di website (QRIS, VA, e-wallet). Akun admin lain tetap lewat WhatsApp.
                @else
                    Semua tombol "Beli" kembali lewat WhatsApp. Pesanan website yang sudah dibuat tetap diproses sampai selesai.
                @endif
            </p>
        </div>
        @if ($gatewayKeys)
            <form method="POST" action="{{ route('admin.orders.gateway') }}" @if ($gatewayOn) data-confirm="Matikan payment gateway? Semua pembelian akan kembali lewat WhatsApp." @endif>
                @csrf @method('PATCH')
                <input type="hidden" name="enabled" value="{{ $gatewayOn ? 0 : 1 }}">
                <button class="{{ $gatewayOn ? 'btn-secondary text-rose-300' : 'btn' }}">{{ $gatewayOn ? 'Matikan (OFF)' : 'Nyalakan (ON)' }}</button>
            </form>
        @endif
    </section>

    <p class="muted mb-5">Pembelian akun milik super admin yang dibayar lewat checkout website (Tripay{{ \App\Services\Tripay::sandbox() ? ' — mode sandbox / uji coba' : '' }}). Akun otomatis menjadi <strong>Terjual</strong> saat pembayaran lunas. Serahkan data akun ke pembeli lewat live chat atau WhatsApp, lalu tekan <strong>Sudah diserahkan</strong>.</p>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        @foreach ($filters as $key => $label)
            <a href="{{ route('admin.orders', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif
                class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $tab === $key ? 'border-lime-300 bg-lime-300 text-slate-950' : 'border-white/10 text-slate-300 hover:text-white' }}">{{ $label }} ({{ $counts[$key] }})</a>
        @endforeach
        <form method="GET" action="{{ route('admin.orders') }}" class="ml-auto flex w-full gap-2 sm:w-auto">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input class="field py-2" type="search" name="q" value="{{ $query }}" maxlength="60" placeholder="Cari kode / nama / nomor / akun" aria-label="Cari pesanan">
            <button class="btn-secondary shrink-0">Cari</button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($orders as $order)
            <article class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $order->merchant_ref }} · {{ $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
                        <h2 class="mt-1 text-lg font-black">{{ $order->items->pluck('title')->implode(', ') }}</h2>
                        <p class="mt-1 text-sm"><span class="font-black text-lime-300">{{ \App\Models\Order::rupiah($order->total) }}</span> <span class="text-slate-400">(akun {{ \App\Models\Order::rupiah($order->subtotal) }} + biaya {{ \App\Models\Order::rupiah($order->fee) }}) · {{ $order->payment_name }}</span></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class(['rounded-full border px-3 py-1 text-xs font-black uppercase tracking-wider', 'border-amber-300/40 text-amber-300' => $order->status === 'unpaid', 'border-emerald-300/40 text-emerald-300' => $order->status === 'paid', 'border-rose-400/40 text-rose-300' => ! in_array($order->status, ['unpaid', 'paid'], true)])>{{ $order->statusLabel() }}</span>
                        <a href="{{ route('admin.orders.chat', $order) }}" class="btn-secondary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" /></svg>
                            Chat pembeli
                        </a>
                        @if ($order->status === 'paid')
                            <form method="POST" action="{{ route('admin.orders.deliver', $order) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="delivered" value="{{ $order->delivered_at ? 0 : 1 }}">
                                <button class="{{ $order->delivered_at ? 'btn-secondary' : 'btn' }}">{{ $order->delivered_at ? 'Batalkan tanda diserahkan' : 'Sudah diserahkan' }}</button>
                            </form>
                        @endif
                    </div>
                </div>
                @if ($order->admin_note)
                    <p class="mt-3 rounded-xl border border-rose-400/30 bg-rose-400/10 p-3 text-sm font-semibold text-rose-200">{{ $order->admin_note }}</p>
                @endif
                <div class="mt-3 rounded-xl border border-white/10 bg-white/5 p-3 text-xs text-slate-300">
                    <span class="font-bold text-white">Pembeli:</span> {{ $order->customer_name }} ·
                    <a class="text-lime-300 hover:underline" href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a> ·
                    <a class="text-lime-300 hover:underline" href="https://wa.me/{{ \App\Models\Report::normaliseWhatsapp($order->customer_phone) }}?text={{ rawurlencode('Halo '.$order->customer_name.', terima kasih sudah membayar pesanan '.$order->merchant_ref.' di ZutoMarket. Berikut data akunnya:') }}" target="_blank" rel="noopener noreferrer">WA {{ $order->customer_phone }}</a>
                    @if ($order->paid_at) · Lunas {{ $order->paid_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} @endif
                    @if ($order->delivered_at) · Diserahkan {{ $order->delivered_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} @endif
                    @if ($order->tripay_reference) · Ref Tripay {{ $order->tripay_reference }} @endif
                </div>
            </article>
        @empty
            <p class="panel p-10 text-center text-sm text-slate-400">Belum ada pesanan di sini.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
@endsection

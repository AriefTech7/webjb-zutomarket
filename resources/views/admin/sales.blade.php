@extends('layouts.admin')
@section('title', 'Data penjualan')
@section('admin-title', 'Data penjualan')
@section('admin-content')
    @php($rupiah = fn (int $amount): string => 'Rp '.number_format($amount, 0, ',', '.'))
    <form class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end" method="GET" action="{{ route('admin.sales') }}">
        <div class="min-w-0 sm:w-56"><label class="label" for="month">Bulan</label><input id="month" class="field"
                type="month" name="month" value="{{ $month->format('Y-m') }}" required></div>
        <button class="btn w-full sm:w-auto">Tampilkan</button>
    </form>
    <div class="mb-6 grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
        @foreach (['Terjual bulan ini' => $monthCount, 'Penjualan bulan ini' => $rupiah($monthRevenue), 'Total terjual' => $totalCount, 'Total penjualan' => $rupiah($totalRevenue)] as $label => $value)
            <div class="panel min-w-0 p-4 md:p-6">
                <p class="muted text-xs md:text-sm">{{ $label }}</p>
                <p class="mt-2 break-words text-xl font-black text-lime-300 md:text-2xl">{{ $value }}</p>
            </div>
        @endforeach
    </div>
    <form class="panel mb-8 grid gap-4 p-5 md:grid-cols-2 md:p-6" method="POST" action="{{ route('admin.sales.store') }}">
        @csrf
        <h2 class="text-lg font-bold md:col-span-2">Tambah penjualan manual</h2>
        <div><label class="label" for="item">Barang atau layanan</label><input id="item" class="field" name="item" value="{{ old('item') }}" maxlength="150" required placeholder="Contoh: Joki Epic 10 bintang"></div>
        <div><label class="label" for="amount">Nilai penjualan (Rupiah)</label><input id="amount" class="field" name="amount" type="number" min="1" max="999999999999" step="1" value="{{ old('amount') }}" required placeholder="60000"></div>
        <div><label class="label" for="sold_on">Tanggal</label><input id="sold_on" class="field" name="sold_on" type="date" value="{{ old('sold_on', now('Asia/Jakarta')->toDateString()) }}" required></div>
        <div><label class="label" for="note">Catatan (opsional)</label><input id="note" class="field" name="note" value="{{ old('note') }}" maxlength="255" placeholder="Contoh: nama pembeli"></div>
        <div class="md:col-span-2"><button class="btn w-full sm:w-auto">Simpan penjualan</button></div>
    </form>
    <h2 class="mb-3 text-lg font-bold">Penjualan manual</h2>
    <ul class="panel" aria-label="Daftar penjualan manual">
        @forelse ($manualSales as $sale)
            <li class="flex items-center justify-between gap-4 border-b border-white/5 p-4 last:border-0 md:p-5">
                <div class="min-w-0">
                    <p class="break-words text-sm font-semibold">{{ $sale->item }}</p>
                    <p class="muted break-words text-xs">{{ $sale->sold_on->format('d M Y') }}@if ($sale->note) · {{ $sale->note }}@endif</p>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <p class="text-sm font-bold text-lime-300">{{ $rupiah($sale->amount) }}</p>
                    <form method="POST" action="{{ route('admin.sales.destroy', $sale) }}" data-confirm="Hapus catatan penjualan ini?">
                        @csrf
                        @method('DELETE')
                        <button class="py-3 text-sm text-rose-300" aria-label="Hapus {{ $sale->item }}">Hapus</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="p-8 text-center text-sm text-slate-400">Belum ada penjualan manual pada bulan ini.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $manualSales->links() }}</div>
    <h2 class="mb-3 mt-8 text-lg font-bold">Akun terjual</h2>
    <ul class="panel" aria-label="Daftar akun terjual">
        @forelse ($accounts as $account)
            <li class="flex items-center justify-between gap-4 border-b border-white/5 p-4 last:border-0 md:p-5">
                <div class="min-w-0">
                    <p class="break-words text-sm font-semibold">{{ $account->title }}</p>
                    <p class="muted text-xs">{{ $account->category->name }} · {{ $account->sold_at->format('d M Y') }}</p>
                </div>
                <p class="shrink-0 text-sm font-bold text-lime-300">{{ $account->formattedPrice() }}</p>
            </li>
        @empty
            <li class="p-10 text-center text-sm text-slate-400">Belum ada akun terjual pada bulan ini.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $accounts->links() }}</div>
    <p class="muted mt-6 text-xs">Dihitung dari akun berstatus Terjual dengan harga yang tercantum, ditambah penjualan manual. Pesanan joki dan hasil nego di WhatsApp perlu dicatat manual. Akun yang dihapus tidak lagi dihitung.</p>
@endsection

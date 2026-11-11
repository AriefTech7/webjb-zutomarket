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
    <div class="mb-4 grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
        @foreach (['Terjual bulan ini' => $monthCount, 'Penjualan bulan ini' => $rupiah($monthRevenue), 'Total terjual' => $totalCount, 'Total penjualan' => $rupiah($totalRevenue)] as $label => $value)
            <div class="panel min-w-0 p-4 md:p-6">
                <p class="muted text-xs md:text-sm">{{ $label }}</p>
                <p class="mt-2 break-words text-xl font-black text-lime-300 md:text-2xl">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <h2 class="mb-3 mt-6 text-lg font-bold">Rincian per jenis · {{ $month->translatedFormat('F Y') }}</h2>
    <div class="panel mb-8 overflow-x-auto">
        <table class="w-full min-w-[420px] text-left text-sm" aria-label="Rincian penjualan per jenis">
            <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-500">
                <tr><th class="p-4">Jenis</th><th class="p-4 text-right">Transaksi</th><th class="p-4 text-right">Nilai</th></tr>
            </thead>
            <tbody>
                @foreach ($breakdown as $type)
                    <tr class="border-b border-white/5">
                        <td class="p-4 font-semibold">{{ $type['label'] }}</td>
                        <td class="p-4 text-right">{{ $type['count'] }}</td>
                        <td class="p-4 text-right font-bold text-lime-300">{{ $rupiah($type['amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-black"><td class="p-4">Total</td><td class="p-4 text-right">{{ $monthCount }}</td><td class="p-4 text-right text-lime-300">{{ $rupiah($monthRevenue) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <form class="panel mb-8 grid gap-4 p-5 md:grid-cols-2 md:p-6" method="POST" action="{{ route('admin.sales.store') }}">
        @csrf
        <h2 class="text-lg font-bold md:col-span-2">Tambah penjualan manual</h2>
        <div><label class="label" for="type">Jenis penjualan</label><select id="type" class="field" name="type" required>
                @foreach (\App\Models\ManualSale::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
        <div><label class="label" for="item">Barang atau layanan</label><input id="item" class="field" name="item" value="{{ old('item') }}" maxlength="150" required placeholder="Contoh: Joki Epic V – Mythic"></div>
        <div><label class="label" for="amount">Nilai penjualan (Rupiah)</label><input id="amount" class="field" name="amount" type="number" min="1" max="999999999999" step="1" value="{{ old('amount') }}" required placeholder="325000"></div>
        <div><label class="label" for="sold_on">Tanggal</label><input id="sold_on" class="field" name="sold_on" type="date" value="{{ old('sold_on', now('Asia/Jakarta')->toDateString()) }}" required></div>
        <div class="md:col-span-2"><label class="label" for="note">Catatan (opsional)</label><input id="note" class="field" name="note" value="{{ old('note') }}" maxlength="255" placeholder="Contoh: nama pembeli"></div>
        <div class="md:col-span-2"><button class="btn w-full sm:w-auto">Simpan penjualan</button></div>
    </form>

    <h2 class="mb-3 text-lg font-bold">Riwayat penjualan · {{ $month->translatedFormat('F Y') }}</h2>
    <div class="panel overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm" aria-label="Riwayat penjualan">
            <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-4">Tanggal</th>
                    <th class="p-4">Jenis</th>
                    <th class="p-4">Barang / layanan</th>
                    <th class="p-4">Catatan</th>
                    <th class="p-4 text-right">Nilai</th>
                    <th class="p-4"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-white/5 align-top">
                        <td class="whitespace-nowrap p-4">{{ $row['date']->format('d M Y') }}</td>
                        <td class="p-4"><span class="status text-slate-200">{{ $row['type'] }}</span>
                            <span class="mt-1 block text-[10px] text-slate-500">{{ $row['source'] }}</span></td>
                        <td class="break-words p-4 font-semibold">{{ $row['item'] }}</td>
                        <td class="break-words p-4 text-slate-400">{{ $row['note'] ?: '-' }}</td>
                        <td class="whitespace-nowrap p-4 text-right font-bold text-lime-300">{{ $rupiah($row['amount']) }}</td>
                        <td class="p-4 text-right">
                            @if ($row['sale'])
                                <form method="POST" action="{{ route('admin.sales.destroy', $row['sale']) }}" data-confirm="Hapus catatan penjualan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm text-rose-300" aria-label="Hapus {{ $row['item'] }}">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-slate-400">Belum ada penjualan pada bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="muted mt-6 text-xs">"Katalog" = akun yang ditandai Terjual, dihitung dengan harga yang tercantum. "Manual" = dicatat lewat formulir di atas. Jangan mencatat manual akun katalog yang sudah ditandai Terjual, agar tidak terhitung dua kali. Akun yang dihapus tidak lagi dihitung.</p>
@endsection

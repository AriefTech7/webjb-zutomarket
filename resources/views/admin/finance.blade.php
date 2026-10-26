@extends('layouts.admin')
@section('title', 'Laporan untung/rugi bulanan')
@section('admin-title', 'Laporan untung/rugi bulanan')
@section('admin-content')
    @php($rupiah = fn($value) => 'Rp ' . number_format($value, 0, ',', '.'))
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <form method="GET" action="{{ route('admin.finance') }}" class="flex flex-wrap items-end gap-3">
            <div><label class="label" for="month">Bulan laporan</label><input class="field" id="month" name="month"
                    type="month" value="{{ $month->format('Y-m') }}" required></div>
            <button class="btn-secondary">Tampilkan</button>
        </form>
        <a class="btn" href="{{ route('admin.finance.create') }}">+ Catat penjualan</a>
    </div>
    @if ($legacySold)
        <div class="panel mb-6 p-4 text-sm text-amber-200">{{ $legacySold }} akun terjual belum memiliki catatan
            penjualan. Lengkapi nominal dan tanggal sebenarnya melalui Konfirmasi penjualan. Data tersebut belum masuk
            laporan.</div>
    @endif
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (['revenue' => 'Total penjualan', 'cost_of_goods' => 'Total modal akun terjual', 'profit' => 'Keuntungan / kerugian', 'sold_units' => 'Jumlah akun terjual'] as $key => $label)
            <article class="panel min-w-0 p-5">
                <h2 class="muted text-sm">{{ $label }}</h2>
                <p class="mt-3 break-words text-2xl font-black {{ $totals[$key] < 0 ? 'text-rose-300' : 'text-lime-300' }}">
                    {{ $key === 'sold_units' ? $totals[$key] : $rupiah($totals[$key]) }}</p>
                @if ($key === 'profit')
                    <p class="muted mt-2 text-xs">
                        {{ $totals[$key] > 0 ? 'Untung' : ($totals[$key] < 0 ? 'Rugi / koreksi refund' : 'Impas') }}</p>
                @endif
                @if ($key === 'sold_units')
                    <p class="muted mt-2 text-xs">Setelah koreksi refund bulan ini</p>
                @endif
            </article>
        @endforeach
    </div>
    <p class="muted mb-6 text-sm">Untung/rugi = harga jual − modal akun terjual. Angka sudah memperhitungkan pembatalan
        penjualan. Ini hasil penjualan, bukan laba bersih seluruh usaha.</p>
    <section class="panel mb-6 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold">Tren untung/rugi · 6 bulan</h2>
            <p class="text-xs"><span class="text-lime-300">■ Untung</span> <span class="ml-3 text-rose-300">■ Rugi /
                    koreksi</span></p>
        </div>
        <div class="mt-5 overflow-x-auto">
            <svg viewBox="0 0 720 300" class="h-72 w-full min-w-[480px]" role="img"
                aria-labelledby="profit-chart-title profit-chart-description">
                <title id="profit-chart-title">Keuntungan dan kerugian bulanan</title>
                <desc id="profit-chart-description">Batang di atas garis nol berarti untung; di bawah berarti rugi atau
                    koreksi refund. Nominal setiap bulan tersedia di rincian grafik.</desc>
                <line x1="30" y1="140" x2="710" y2="140" stroke="#64748b" />
                <text x="5" y="144" fill="#94a3b8" font-size="11">0</text>
                @foreach ($trend as $period)
                    @php($height = (abs($period['profit']) / $chartMaximum) * 100)
                    @php($x = 55 + $loop->index * 110)
                    <rect x="{{ $x }}" y="{{ $period['profit'] >= 0 ? 140 - $height : 140 }}" width="50"
                        height="{{ $height }}" fill="{{ $period['profit'] >= 0 ? '#bef264' : '#fda4af' }}">
                        <title>{{ $period['label'] }}: {{ $rupiah($period['profit']) }}</title>
                    </rect>
                    <text x="{{ $x + 25 }}" y="275" text-anchor="middle" fill="#94a3b8"
                        font-size="12">{{ $period['label'] }}</text>
                @endforeach
            </svg>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6" aria-label="Rincian untung rugi bulanan">
            @foreach ($trend as $period)
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="muted text-xs">{{ $period['label'] }}</p>
                    <p
                        class="mt-1 break-words text-sm font-semibold {{ $period['profit'] < 0 ? 'text-rose-300' : 'text-lime-300' }}">
                        {{ $rupiah($period['profit']) }}</p>
                </div>
            @endforeach
        </div>
    </section>
    <h2 class="mb-2 text-xl font-bold">Rincian penjualan</h2>
    <p class="muted mb-4 text-sm">Refund dicatat sebagai koreksi hasil penjualan pada bulan pengembalian, bukan sebagai
        kerugian penjualan baru.</p>
    <div class="panel overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <caption class="sr-only">Rincian penjualan bulan {{ $month->format('m/Y') }}</caption>
            <thead class="border-b border-white/10 text-xs uppercase text-slate-400">
                <tr>
                    @foreach (['Tanggal', 'Akun / transaksi', 'Harga jual', 'Modal', 'Untung / rugi', 'Tindakan'] as $heading)
                        <th class="p-4" scope="col">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    @php($profit = $transaction->revenue - $transaction->cost_of_goods)
                    <tr class="border-b border-white/5 align-top">
                        <td class="whitespace-nowrap p-4">{{ $transaction->occurred_on->format('d/m/Y') }}<span
                                class="muted mt-1 block text-xs">#{{ $transaction->id }}</span></td>
                        <td class="max-w-sm p-4">
                            <p class="font-semibold">{{ $transaction->description }}</p>
                            <p class="muted mt-1 text-xs">
                                {{ $transaction->type === 'reversal' ? 'Koreksi refund' : $transaction->label() }}{{ $transaction->category_name ? ' · ' . $transaction->category_name : '' }}
                            </p>
                            @if ($transaction->type === 'trade_in' && $transaction->received_account_id && !$transaction->reversal)
                                <a class="mt-1 block text-xs text-sky-200 underline"
                                    href="{{ route('admin.accounts.edit', $transaction->received_account_id) }}">Lengkapi
                                    stok ↗</a>
                            @endif
                            @if ($transaction->reversal)
                                <p class="mt-2 text-xs text-amber-200">Dibatalkan
                                    {{ $transaction->reversal->occurred_on->format('d/m/Y') }} · koreksi
                                    #{{ $transaction->reversal->id }}</p>
                            @endif
                            @if ($transaction->notes)
                                <details class="mt-2">
                                    <summary class="muted cursor-pointer text-xs">Catatan</summary>
                                    <p class="muted mt-1 whitespace-pre-line break-words text-xs">{{ $transaction->notes }}
                                    </p>
                                </details>
                            @endif
                        </td>
                        <td class="whitespace-nowrap p-4">{{ $rupiah($transaction->revenue) }}</td>
                        <td class="whitespace-nowrap p-4">{{ $rupiah($transaction->cost_of_goods) }}</td>
                        <td class="whitespace-nowrap p-4 {{ $profit < 0 ? 'text-rose-300' : 'text-lime-300' }}">
                            {{ $rupiah($profit) }}</td>
                        <td class="w-64 p-4">
                            @if ($transaction->type !== 'reversal' && !$transaction->reversal)
                                <details>
                                    <summary class="cursor-pointer text-amber-200">Batalkan penjualan</summary>
                                    <form class="mt-3 space-y-3" method="POST"
                                        action="{{ route('admin.finance.reverse', $transaction) }}"
                                        data-confirm="Pastikan akun dan pembayaran telah dikembalikan sepenuhnya. Batalkan penjualan?">
                                        @csrf
                                        <p class="muted text-xs">Pembatalan penuh mengoreksi penjualan dan modal; stok
                                            kembali Draft. Refund sebagian belum didukung.</p>
                                        <label class="label" for="reverse-date-{{ $transaction->id }}">Tanggal
                                            refund</label><input class="field" id="reverse-date-{{ $transaction->id }}"
                                            name="occurred_on" type="date"
                                            min="{{ $transaction->occurred_on->toDateString() }}"
                                            max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}"
                                            required>
                                        <label class="label" for="reverse-reason-{{ $transaction->id }}">Alasan</label>
                                        <textarea class="field" id="reverse-reason-{{ $transaction->id }}" name="reason" maxlength="5000" required></textarea>
                                        <button class="btn-secondary">Konfirmasi pembatalan</button>
                                    </form>
                                </details>
                            @else<span class="muted">Riwayat tersimpan</span>
                            @endif
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="6" class="p-10 text-center text-slate-400">Belum ada transaksi pada bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $transactions->links() }}</div>
@endsection

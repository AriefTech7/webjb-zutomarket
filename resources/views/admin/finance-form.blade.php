@extends('layouts.admin')
@section('title', 'Catat transaksi')
@section('admin-title', 'Catat transaksi keuangan')
@section('admin-content')
<nav class="mb-6 flex flex-wrap gap-2" aria-label="Jenis transaksi">
    @foreach(['sale' => 'Penjualan biasa', 'trade_in' => 'Tukar tambah'] as $value => $label)
        <a class="btn-secondary {{ $type === $value ? 'text-lime-300' : '' }}" href="{{ route('admin.finance.create', ['type' => $value]) }}" @if($type === $value) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
<form method="POST" action="{{ route('admin.finance.store') }}" class="panel max-w-3xl space-y-6 p-5 md:p-8" data-confirm="Pastikan pembayaran dan serah terima sudah selesai. Simpan transaksi ini?">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="submission_id" value="{{ old('submission_id', (string) \Illuminate\Support\Str::uuid()) }}">
    <p class="muted text-sm">Catat hanya transaksi yang benar-benar selesai. Tanggal berikut menentukan bulan laporan, bukan tanggal akun dibuat.</p>
    <div>
        <label class="label" for="occurred_on">Tanggal transaksi / pembayaran</label>
        <input class="field" id="occurred_on" name="occurred_on" type="date" max="{{ now()->toDateString() }}" value="{{ old('occurred_on', now()->toDateString()) }}" required>
    </div>
        <div>
            <label class="label" for="game_account_id">Akun milik toko</label>
            <select class="field" id="game_account_id" name="game_account_id" required>
                <option value="">Pilih akun</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" @selected(old('game_account_id', $selectedAccount?->id) == $account->id)>{{ $account->title }} — {{ $account->formattedPrice() }}{{ $account->status === 'sold' ? ' (penjualan lama belum tercatat)' : '' }}</option>
                @endforeach
            </select>
            @if($accounts->isEmpty())<p class="muted mt-2 text-sm">Tidak ada stok yang memenuhi syarat. <a class="text-lime-300" href="{{ route('admin.accounts.create') }}">Tambah akun terlebih dahulu.</a></p>@endif
        </div>
    <div>
        <label class="label" for="amount">Harga jual final akun toko (Rupiah)</label>
        <input class="field" id="amount" name="amount" type="number" min="0" max="999999999999" step="1" value="{{ old('amount', $selectedAccount?->price) }}" required>
    </div>
        <div>
            <label class="label" for="cost_price">Modal akun toko jika belum tercatat (Rupiah)</label>
            <input class="field" id="cost_price" name="cost_price" type="number" min="0" max="999999999999" step="1" value="{{ old('cost_price', $selectedAccount?->cost_price) }}">
            <p class="muted mt-2 text-xs">Modal yang sudah tersimpan digunakan otomatis. Jika belum ada, wajib isi; 0 berarti benar-benar tanpa modal. Modal tidak dicatat sebagai uang keluar.</p>
        </div>
    @if($type === 'trade_in')
        <div><label class="label" for="trade_request_id">Pengajuan yang telah diterima</label><select class="field" id="trade_request_id" name="trade_request_id" required><option value="">Pilih pengajuan</option>@foreach($tradeRequests as $trade)<option value="{{ $trade->id }}" @selected(old('trade_request_id', $selectedTrade) == $trade->id)>#{{ $trade->id }} · {{ $trade->name }} · {{ $trade->category->name }}</option>@endforeach</select></div>
        <div><label class="label" for="received_title">Nama akun pelanggan yang diterima toko</label><input class="field" id="received_title" name="received_title" maxlength="150" value="{{ old('received_title') }}" required></div>
        <div><label class="label" for="trade_value">Nilai kesepakatan akun pelanggan (Rupiah)</label><input class="field" id="trade_value" name="trade_value" type="number" min="0" max="999999999999" step="1" value="{{ old('trade_value') }}" required></div>
        <div class="rounded-xl border border-sky-300/20 bg-sky-300/5 p-4 text-sm text-sky-200">Uang masuk = harga jual dikurangi nilai akun pelanggan. Jika hasilnya negatif, selisih dicatat sebagai uang keluar toko. Akun pelanggan otomatis menjadi stok Draft dengan modal sesuai nilai kesepakatan. Lengkapi foto dan spesifikasinya sebelum diterbitkan.</div>
    @endif
    <div><label class="label" for="notes">Catatan / referensi pembayaran (opsional)</label><textarea class="field" id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes') }}</textarea></div>
    <div class="flex flex-wrap gap-3"><button class="btn">Konfirmasi dan catat transaksi</button><a class="btn-secondary" href="{{ route('admin.finance') }}">Kembali</a></div>
</form>
@endsection

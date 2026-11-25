@extends('layouts.admin')
@section('title', 'Voucher')
@section('admin-title', 'Voucher')
@section('admin-content')
    <p class="muted mb-5">Kode voucher dipakai pembeli di halaman <strong>checkout website</strong> (akun yang dibayar lewat Tripay). Pembelian lewat WhatsApp tidak memakai voucher otomatis. Satu pemakaian dihitung saat pesanan dibuat dan dilepas lagi kalau pembayaran kedaluwarsa.</p>

    <form method="POST" action="{{ route('admin.vouchers.store') }}" class="panel mb-6 grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4" data-voucher-form>
        @csrf
        <h2 class="font-black md:col-span-2 xl:col-span-4">Buat kode voucher</h2>
        <div>
            <label class="label" for="code">Kode voucher</label>
            <input class="field uppercase" id="code" name="code" value="{{ old('code', $suggestion) }}" maxlength="30" required placeholder="Contoh: HEMAT10" autocomplete="off">
            <p class="muted mt-1 text-xs">Huruf/angka tanpa spasi. Boleh diganti.</p>
        </div>
        <div>
            <label class="label" for="type">Jenis potongan</label>
            <select class="field" id="type" name="type">
                @foreach (\App\Models\Voucher::TYPES as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" @selected(old('type', 'percent') === $typeValue)>{{ $typeLabel }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="value">Besar potongan</label>
            <input class="field" id="value" name="value" value="{{ old('value') }}" inputmode="numeric" required placeholder="10 (persen) atau 25.000 (rupiah)">
        </div>
        <div>
            <label class="label" for="max_discount">Maks potongan (Rp)</label>
            <input class="field" id="max_discount" name="max_discount" value="{{ old('max_discount') }}" inputmode="numeric" placeholder="Opsional, untuk persen">
        </div>
        <div>
            <label class="label" for="min_purchase">Minimal belanja (Rp)</label>
            <input class="field" id="min_purchase" name="min_purchase" value="{{ old('min_purchase') }}" inputmode="numeric" placeholder="Kosong = tanpa minimal">
        </div>
        <div>
            <label class="label" for="quota">Kuota total</label>
            <input class="field" id="quota" name="quota" type="number" min="1" value="{{ old('quota') }}" placeholder="Kosong = tanpa batas">
        </div>
        <div>
            <label class="label" for="per_user">Batas per pembeli</label>
            <input class="field" id="per_user" name="per_user" type="number" min="1" value="{{ old('per_user', 1) }}" placeholder="Kosong = tanpa batas">
        </div>
        <div>
            <label class="label" for="note">Catatan (opsional)</label>
            <input class="field" id="note" name="note" value="{{ old('note') }}" maxlength="120" placeholder="Contoh: promo ulang tahun">
        </div>
        <div>
            <label class="label" for="starts_at">Mulai berlaku (WIB)</label>
            <input class="field" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}">
        </div>
        <div>
            <label class="label" for="ends_at">Berakhir (WIB)</label>
            <input class="field" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}">
        </div>
        <div class="flex items-end md:col-span-2">
            <button class="btn w-full sm:w-auto">Buat voucher</button>
        </div>
    </form>

    <div class="space-y-3">
        @forelse ($vouchers as $voucher)
            @php($expired = $voucher->ends_at?->isPast())
            <article class="panel flex flex-wrap items-center justify-between gap-4 p-4">
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-2">
                        <span class="rounded-lg border border-dashed border-lime-300/50 px-2 py-0.5 font-mono text-base font-black tracking-wider text-lime-300">{{ $voucher->code }}</span>
                        <span class="font-black">{{ $voucher->label() }}</span>
                        <span @class(['rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wider', 'border-emerald-300/40 text-emerald-300' => $voucher->is_active && ! $expired, 'border-rose-400/40 text-rose-300' => ! $voucher->is_active || $expired])>{{ $expired ? 'Kedaluwarsa' : ($voucher->is_active ? 'Aktif' : 'Nonaktif') }}</span>
                    </p>
                    <p class="muted mt-1 text-xs">
                        Dipakai {{ $voucher->used_count }}{{ $voucher->quota ? ' / '.$voucher->quota : '' }}
                        · {{ $voucher->per_user ? $voucher->per_user.'× per pembeli' : 'tanpa batas per pembeli' }}
                        @if ($voucher->min_purchase) · min. belanja {{ \App\Models\Order::rupiah($voucher->min_purchase) }} @endif
                        @if ($voucher->starts_at) · mulai {{ $voucher->starts_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} @endif
                        @if ($voucher->ends_at) · sampai {{ $voucher->ends_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB @endif
                        @if ($voucher->note) · {{ $voucher->note }} @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.vouchers.toggle', $voucher) }}">
                        @csrf @method('PATCH')
                        <button class="btn-secondary">{{ $voucher->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}" data-confirm="Hapus voucher {{ $voucher->code }}?">
                        @csrf @method('DELETE')
                        <button class="btn-secondary text-rose-300">Hapus</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="panel p-10 text-center text-sm text-slate-400">Belum ada voucher. Buat kode pertama di atas.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $vouchers->links() }}</div>
@endsection

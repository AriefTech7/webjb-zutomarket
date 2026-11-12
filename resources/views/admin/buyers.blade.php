@extends('layouts.admin')
@section('title', 'Akun pembeli')
@section('admin-title', 'Akun pembeli')
@section('admin-content')
    <p class="muted mb-5">Pembeli yang daftar di website. Kalau pembeli lupa password dan menghubungi lewat WhatsApp, cari email-nya lalu klik <strong>Buat password sementara</strong>, kirim password itu ke pembeli, dan minta pembeli menggantinya di <strong>Profil → Ubah Password</strong>.</p>

    @if ($temp = session('temporary_password'))
        @php
            $buyerWa = preg_replace('/\D+/', '', (string) $temp['whatsapp']);
            $buyerWa = str_starts_with($buyerWa, '0') ? '62'.substr($buyerWa, 1) : $buyerWa;
            $message = "Halo, ini admin ZutoMarket. Password sementara untuk akun {$temp['email']}: {$temp['password']}\nSilakan masuk di ".route('account.login').' lalu segera ganti password di Profil → Ubah Password.';
        @endphp
        <div class="panel mb-6 border-lime-300/40 p-5" role="status">
            <p class="text-sm text-slate-300">Password sementara untuk <strong class="text-white">{{ $temp['email'] }}</strong> (hanya tampil sekali):</p>
            <p class="mt-2 select-all font-mono text-2xl font-black tracking-wider text-lime-300">{{ $temp['password'] }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @if (strlen($buyerWa) >= 9)
                    <a class="btn" href="https://wa.me/{{ $buyerWa }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener noreferrer">Kirim ke WhatsApp pembeli</a>
                @else
                    <p class="muted text-xs">Pembeli belum mengisi nomor WhatsApp di profil. Kirim password ini lewat chat WhatsApp yang dia pakai untuk menghubungi admin.</p>
                @endif
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.buyers') }}" class="mb-5 flex gap-2" role="search">
        <label class="sr-only" for="buyer-search">Cari pembeli</label>
        <input id="buyer-search" class="field" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Cari email, nama, atau nomor WhatsApp">
        <button class="btn shrink-0">Cari</button>
    </form>

    @if ($buyers->isEmpty())
        <div class="panel p-10 text-center text-slate-400">{{ $search !== '' ? 'Tidak ada pembeli yang cocok.' : 'Belum ada pembeli yang mendaftar.' }}</div>
    @else
        <div class="panel overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-400">
                    <tr><th class="p-4">Pembeli</th><th class="p-4">WhatsApp</th><th class="p-4">Masuk via</th><th class="p-4">Bergabung</th><th class="p-4 text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($buyers as $buyer)
                        <tr class="border-b border-white/5 last:border-0">
                            <td class="p-4"><p class="font-bold text-white">{{ $buyer->name }}</p><p class="text-xs text-slate-400">{{ $buyer->email }}</p></td>
                            <td class="p-4 text-slate-300">{{ $buyer->whatsapp ?: '-' }}</td>
                            <td class="p-4 text-slate-300">{{ $buyer->google_id ? 'Google' : 'Email' }}</td>
                            <td class="p-4 text-slate-300">{{ $buyer->created_at?->format('d/m/Y') }}</td>
                            <td class="p-4 text-right">
                                <form method="POST" action="{{ route('admin.buyers.password', $buyer) }}" data-confirm="Buat password sementara untuk {{ $buyer->email }}? Password lama pembeli tidak bisa dipakai lagi.">
                                    @csrf
                                    <button class="btn-secondary whitespace-nowrap">Buat password sementara</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $buyers->links() }}</div>
    @endif
@endsection

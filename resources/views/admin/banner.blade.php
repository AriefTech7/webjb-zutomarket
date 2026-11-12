@extends('layouts.admin')
@section('title', 'Banner')
@section('admin-title', 'Banner beranda')
@section('admin-content')
    <p class="muted mb-5">Gambar besar di bagian paling atas halaman utama. Ukuran yang disarankan <strong>1280 × 430 px</strong> (rasio sekitar 3:1). JPG, PNG, atau WebP, maksimal 4 MB.</p>

    <div class="panel mb-6 p-4 md:p-5">
        <p class="mb-3 text-sm font-bold">Banner sekarang {{ $banner['custom'] ? '' : '(bawaan)' }}</p>
        <img class="w-full rounded-2xl border border-white/10" src="{{ $banner['url'] }}" alt="Banner beranda saat ini" width="{{ $banner['width'] }}" height="{{ $banner['height'] }}">
        @if ($banner['link'])
            <p class="muted mt-3 text-xs">Diklik menuju: <a class="text-lime-300 hover:underline" href="{{ $banner['link'] }}" target="_blank" rel="noopener noreferrer">{{ $banner['link'] }}</a></p>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.banner.update') }}" enctype="multipart/form-data" class="panel grid gap-4 p-5 md:p-6">
        @csrf @method('PUT')
        <h2 class="text-lg font-bold">Ganti banner</h2>
        <div><label class="label" for="banner-image">Gambar baru</label>
            <input class="field" id="banner-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <p class="muted mt-2 text-xs">Kosongkan kalau hanya ingin mengubah tautan.</p></div>
        <div><label class="label" for="banner-link">Tautan saat banner diklik (opsional)</label>
            <input class="field" id="banner-link" name="link_url" value="{{ old('link_url', $banner['link']) }}" maxlength="500" placeholder="https://zutomarket.id/flash-sale">
            <p class="muted mt-2 text-xs">Misalnya ke halaman Flash Sale atau promo. Kosongkan kalau banner tidak perlu bisa diklik.</p></div>
        <div><button class="btn">Simpan banner</button></div>
    </form>

    @if ($banner['custom'])
        <form method="POST" action="{{ route('admin.banner.reset') }}" class="mt-4" data-confirm="Kembalikan ke banner bawaan? Gambar yang diunggah akan dihapus.">
            @csrf @method('DELETE')
            <button class="btn-secondary text-rose-300">Kembalikan banner bawaan</button>
        </form>
    @endif
@endsection

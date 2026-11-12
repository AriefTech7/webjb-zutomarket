@extends('layouts.admin')
@section('title', 'Banner')
@section('admin-title', 'Banner beranda')
@section('admin-content')
    <p class="muted mb-5">Banner tampil bergeser otomatis (slider) di bagian paling atas halaman utama. Ukuran yang disarankan <strong>1280 × 430 px</strong> (rasio sekitar 3:1), semua banner sebaiknya berukuran sama. JPG, PNG, atau WebP, maksimal 4 MB. Urutan kecil tampil lebih dulu. Maksimal {{ \App\Http\Controllers\AdminBannerController::MAX_BANNERS }} banner.</p>

    @if ($banners->count() < \App\Http\Controllers\AdminBannerController::MAX_BANNERS)
        <form method="POST" action="{{ route('admin.banner.store') }}" enctype="multipart/form-data" class="panel mb-8 grid gap-4 p-5 md:grid-cols-2 md:p-6">
            @csrf
            <h2 class="text-lg font-bold md:col-span-2">Tambah banner</h2>
            <div class="md:col-span-2"><label class="label" for="new-banner-image">Gambar banner</label>
                <input class="field" id="new-banner-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required></div>
            <div><label class="label" for="new-banner-link">Tautan saat diklik (opsional)</label>
                <input class="field" id="new-banner-link" name="link_url" value="{{ old('link_url') }}" maxlength="500" placeholder="https://zutomarket.id/flash-sale"></div>
            <div><label class="label" for="new-banner-order">Urutan</label>
                <input class="field" id="new-banner-order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', $banners->max('sort_order') + 1) }}"></div>
            <div class="md:col-span-2"><button class="btn">Tambah banner</button></div>
        </form>
    @endif

    <h2 class="mb-3 text-lg font-bold">Banner aktif ({{ $banners->count() }})</h2>
    @if ($banners->isEmpty())
        <div class="panel p-5">
            <p class="muted mb-3 text-sm">Belum ada banner. Halaman utama memakai banner bawaan ini:</p>
            <img class="w-full rounded-2xl border border-white/10" src="{{ asset(\App\Models\Banner::DEFAULT_IMAGE) }}" alt="Banner bawaan" width="1280" height="430">
        </div>
    @endif
    <div class="space-y-4">
        @foreach ($banners as $banner)
            <div class="panel p-4 md:p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-sm font-bold">Banner {{ $loop->iteration }} <span class="muted font-normal">· urutan {{ $banner->sort_order }} · {{ $banner->width }} × {{ $banner->height }} px</span></p>
                    <form method="POST" action="{{ route('admin.banner.destroy', $banner) }}" data-confirm="Hapus banner ini?">
                        @csrf @method('DELETE')
                        <button class="btn-secondary text-rose-300">Hapus</button>
                    </form>
                </div>
                <img class="w-full rounded-2xl border border-white/10" src="{{ $banner->imageUrl() }}" alt="Banner {{ $loop->iteration }}" width="{{ $banner->width }}" height="{{ $banner->height }}" loading="lazy">
                <form method="POST" action="{{ route('admin.banner.update', $banner) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_8rem_auto] md:items-end">
                    @csrf @method('PUT')
                    <div><label class="label text-xs" for="banner-image-{{ $banner->id }}">Ganti gambar (opsional)</label>
                        <input class="field" id="banner-image-{{ $banner->id }}" name="image" type="file" accept="image/jpeg,image/png,image/webp"></div>
                    <div><label class="label text-xs" for="banner-link-{{ $banner->id }}">Tautan saat diklik</label>
                        <input class="field" id="banner-link-{{ $banner->id }}" name="link_url" value="{{ $banner->link_url }}" maxlength="500" placeholder="Kosong = tidak bisa diklik"></div>
                    <div><label class="label text-xs" for="banner-order-{{ $banner->id }}">Urutan</label>
                        <input class="field" id="banner-order-{{ $banner->id }}" name="sort_order" type="number" min="0" max="999" value="{{ $banner->sort_order }}"></div>
                    <button class="btn">Simpan</button>
                </form>
            </div>
        @endforeach
    </div>
@endsection

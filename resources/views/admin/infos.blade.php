@extends('layouts.admin')
@section('title', 'Info terbaru')
@section('admin-title', 'Info terbaru')
@section('admin-content')
    <p class="muted mb-5">Poster di sini tampil di bagian <strong>Info Terbaru</strong> pada halaman utama, bisa digeser oleh pembeli. Ukuran yang disarankan 600 × 800 px (rasio 3:4). Urutan kecil tampil lebih dulu.</p>
    <form action="{{ route('admin.infos.store') }}" method="POST" enctype="multipart/form-data" class="panel mb-8 grid gap-4 p-5 md:grid-cols-2 md:p-6">
        @csrf
        <h2 class="text-lg font-bold md:col-span-2">Tambah info</h2>
        <div><label class="label" for="new-info-title">Judul</label><input class="field" id="new-info-title" name="title" value="{{ old('title') }}" maxlength="100" required placeholder="Contoh: Cara beli akun"></div>
        <div><label class="label" for="new-info-image">Gambar poster</label><input class="field" id="new-info-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required>
            <p class="muted mt-2 text-xs">JPG, PNG, atau WebP. Maksimal 4 MB.</p></div>
        <div><label class="label" for="new-info-link">Tautan (opsional)</label><input class="field" id="new-info-link" name="link_url" value="{{ old('link_url') }}" maxlength="500" placeholder="https://...">
            <p class="muted mt-2 text-xs">Kosongkan agar poster dibuka dalam ukuran penuh saat diklik.</p></div>
        <div><label class="label" for="new-info-order">Urutan</label><input class="field" id="new-info-order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', 0) }}"></div>
        <div class="md:col-span-2"><button class="btn w-full sm:w-auto">+ Tambah info</button></div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($infos as $info)
            <div class="panel overflow-hidden">
                <img class="aspect-[3/4] w-full bg-[#080f25] object-cover" src="{{ $info->imageUrl() }}" alt="{{ $info->title }}" loading="lazy">
                <form action="{{ route('admin.infos.update', $info) }}" method="POST" enctype="multipart/form-data" class="space-y-3 p-4">
                    @csrf @method('PUT')
                    <div><label class="label" for="info-title-{{ $info->id }}">Judul</label><input class="field" id="info-title-{{ $info->id }}" name="title" value="{{ $info->title }}" maxlength="100" required></div>
                    <div><label class="label" for="info-link-{{ $info->id }}">Tautan</label><input class="field" id="info-link-{{ $info->id }}" name="link_url" value="{{ $info->link_url }}" maxlength="500" placeholder="Kosong = buka gambar"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="label" for="info-order-{{ $info->id }}">Urutan</label><input class="field" id="info-order-{{ $info->id }}" name="sort_order" type="number" min="0" max="999" value="{{ $info->sort_order }}"></div>
                        <div><label class="label" for="info-image-{{ $info->id }}">Ganti gambar</label><input class="field" id="info-image-{{ $info->id }}" name="image" type="file" accept="image/jpeg,image/png,image/webp"></div>
                    </div>
                    <button class="btn-secondary w-full">Simpan perubahan</button>
                </form>
                <form method="POST" action="{{ route('admin.infos.destroy', $info) }}" class="px-4 pb-4" data-confirm="Hapus info ini beserta gambarnya?">
                    @csrf @method('DELETE')
                    <button class="min-h-9 w-full rounded-lg border border-rose-300/20 px-4 text-xs text-rose-300 transition hover:bg-rose-300/10 md:min-h-11 md:text-sm">Hapus info</button>
                </form>
            </div>
        @empty
            <p class="panel p-10 text-center text-sm text-slate-400 sm:col-span-2 xl:col-span-3">Belum ada info. Tambahkan poster pertama di atas.</p>
        @endforelse
    </div>
@endsection

@extends('layouts.admin')
@section('title', 'Kelola kategori')
@section('admin-title', 'Kategori game')
@section('admin-content')
    <form action="{{ route('admin.categories.store') }}" method="POST"
        class="panel mb-7 grid items-end gap-4 p-5 md:grid-cols-[1fr_1fr_auto]">
        @csrf<div><label class="label" for="new-name">Nama kategori baru</label><input class="field" name="name"
                id="new-name" value="{{ old('name') }}" required maxlength="100" placeholder="Contoh: Mobile Legends"></div>
        <div><label class="label" for="new-slug">Slug (otomatis jika kosong)</label><input class="field" name="slug"
                id="new-slug" value="{{ old('slug') }}" maxlength="120"></div><button class="btn">+ Tambah
            kategori</button>
    </form>
    <div class="space-y-4">
        @forelse($categories as $category)
            <div class="panel p-5">
                <form action="{{ route('admin.categories.update', $category) }}" method="POST"
                    class="grid items-end gap-4 md:grid-cols-[1fr_1fr_auto]">@csrf @method('PUT')<div><label
                            class="label" for="name-{{ $category->id }}">Nama kategori</label><input class="field"
                            name="name" id="name-{{ $category->id }}" value="{{ $category->name }}" required></div>
                    <div><label class="label" for="slug-{{ $category->id }}">Slug</label><input class="field"
                            name="slug" id="slug-{{ $category->id }}" value="{{ $category->slug }}" required></div>
                    <button class="btn-secondary">Simpan perubahan</button>
                </form>
                <div class="mt-3 flex flex-col gap-3 border-t border-white/10 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="muted text-xs">{{ $category->accounts_count }} akun menggunakan kategori ini</p>
                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                        data-confirm="Hapus kategori ini?">@csrf @method('DELETE')<button
                            class="min-h-11 w-full rounded-xl border border-rose-300/20 px-4 text-sm text-rose-300 transition hover:bg-rose-300/10 sm:w-auto">Hapus kategori</button></form>
                </div>
        </div>@empty<p class="panel p-10 text-center text-slate-400">Belum ada kategori game.</p>
        @endforelse
    </div>
@endsection

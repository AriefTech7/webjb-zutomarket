@extends('layouts.admin')
@section('title', $account->exists ? 'Edit akun' : 'Tambah akun')
@section('admin-title', $account->exists ? 'Edit akun game' : 'Tambah akun game')
@section('admin-content')
    <form method="POST" enctype="multipart/form-data"
        action="{{ $account->exists ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}"
        class="panel max-w-4xl space-y-6 p-5 md:p-8">
        @csrf @if ($account->exists)
            @method('PUT')
        @endif
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="label" for="title">Judul akun</label><input class="field" id="title" name="title"
                    value="{{ old('title', $account->title) }}" required maxlength="150"></div>
            <div><label class="label" for="slug">Slug URL (otomatis jika kosong)</label><input class="field"
                    id="slug" name="slug" value="{{ old('slug', $account->slug) }}" maxlength="180"></div>
            <div><label class="label" for="category_id">Kategori game</label><select class="field" id="category_id"
                    name="category_id" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $account->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @if ($categories->isEmpty())
                    <a class="mt-2 block text-sm text-lime-300" href="{{ route('admin.categories') }}">Tambahkan kategori
                        terlebih dahulu →</a>
                @endif
            </div>
            <div><label class="label" for="price">Harga (Rupiah)</label><input class="field" id="price"
                    name="price" type="number" min="0" max="999999999999" step="1"
                    value="{{ old('price', $account->price) }}" required></div>
        </div>
        <div><label class="label" for="description">Deskripsi</label>
            <textarea class="field" id="description" name="description" rows="4" required>{{ old('description', $account->description) }}</textarea>
        </div>
        <div><label class="label" for="specifications">Spesifikasi akun</label>
            <textarea class="field" id="specifications" name="specifications" rows="6"
                placeholder="Rank: Mythic&#10;Level: 60&#10;Skin: 120&#10;Hero: 90&#10;Server: Indonesia" required>{{ old('specifications', $account->specifications) }}</textarea>
            <p class="muted mt-2 text-xs">Tulis rank, level, skin, item, hero, dan server di sini. Semua teks dapat dicari
                pembeli.</p>
        </div>
        <div><label class="label" for="status">Status akun</label><select class="field" id="status" name="status">
                @foreach (['draft' => 'Draft / disembunyikan', 'available' => 'Tersedia', 'sold' => 'Terjual'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $account->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="label" for="main_image">Gambar utama
                {{ $account->exists ? '(opsional untuk mengganti)' : '*' }}</label>
            @if ($account->exists)
                <img src="{{ $account->imageUrl() }}" class="mb-3 h-28 rounded-lg" alt="Gambar utama saat ini">
            @endif
            <input class="field" id="main_image" name="main_image" type="file" accept="image/jpeg,image/png,image/webp"
                @required(!$account->exists)>
            <p class="muted mt-2 text-xs">JPG, PNG, atau WebP. Maksimal 4 MB per gambar.</p>
        </div>
        <div><label class="label" for="images">Gambar tambahan (maksimal 6)</label><input class="field" id="images"
                name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp">
            @if ($account->exists)
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($account->images as $image)
                        <label class="panel p-3"><img src="{{ Storage::disk('public')->url($image->image_path) }}"
                                class="mb-2 h-24 w-full rounded-lg object-cover" alt="Gambar tambahan"><span
                                class="text-xs text-rose-300"><input type="checkbox" name="remove_images[]"
                                    value="{{ $image->id }}"> Hapus gambar ini</span></label>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-3"><button class="btn">Simpan akun</button><a class="btn-secondary"
                href="{{ route('admin.accounts') }}">Batal</a></div>
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Ajukan tukar tambah akun')
@section('description', 'Ajukan tukar tambah akun game dengan mengirim data akun dan screenshot untuk direview admin SHOPGAME ZUTO.')
@section('content')
<section class="shell py-10">
    <div class="mb-8 max-w-3xl"><p class="eyebrow">Trade-in request</p><h1 class="mt-3 text-4xl font-black leading-tight">Ajukan tukar tambah akun game<span class="text-lime-300">.</span></h1><p class="mt-4 leading-7 text-slate-400">Isi detail akun yang ingin Anda jual atau tukar tambah. Admin akan mengecek data dan menghubungi Anda melalui WhatsApp.</p></div>
    <form data-trade-form action="{{ route('trade.store') }}" method="POST" enctype="multipart/form-data" class="panel grid gap-6 p-5 md:p-8 lg:grid-cols-2">
        @csrf
        <div><label class="label" for="name">Nama</label><input class="field" id="name" name="name" required maxlength="100" value="{{ old('name') }}" autocomplete="name"></div>
        <div><label class="label" for="whatsapp_number">Nomor WhatsApp aktif</label><input class="field" id="whatsapp_number" name="whatsapp_number" required inputmode="numeric" pattern="[1-9][0-9]{7,14}" value="{{ old('whatsapp_number') }}" placeholder="6281234567890"><p class="muted mt-2 text-xs">Gunakan format 628xxxxxxxxxx tanpa +, spasi, atau tanda hubung.</p></div>
        <div><label class="label" for="email">Email</label><input class="field" id="email" name="email" type="email" required maxlength="255" value="{{ old('email') }}" autocomplete="email"></div>
        <div><label class="label" for="category_id">Kategori game yang dijual</label><select class="field" id="category_id" name="category_id" required><option value="">Pilih game</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div><label class="label" for="estimated_price">Estimasi harga menurut Anda (opsional)</label><input class="field" id="estimated_price" name="estimated_price" type="number" min="0" max="999999999999" step="1" value="{{ old('estimated_price') }}" placeholder="Contoh: 350000"></div>
        <div><label class="label" for="desired_account">Akun game yang ingin ditambah (opsional)</label><input class="field" id="desired_account" name="desired_account" maxlength="180" value="{{ old('desired_account') }}" placeholder="Contoh: ML Mythic banyak skin"></div>
        <div class="lg:col-span-2"><label class="label" for="description">Deskripsi akun game</label><textarea class="field" id="description" name="description" rows="7" required minlength="20" placeholder="Tulis rank, level, skin, hero/item penting, server, kondisi akun, dan informasi lain yang perlu dicek admin.">{{ old('description') }}</textarea></div>
        <div class="lg:col-span-2 min-w-0"><label class="label" for="screenshots">Screenshot akun game</label><input class="field" id="screenshots" name="screenshots[]" type="file" multiple required accept="image/jpeg,image/png,image/webp" aria-describedby="upload-help"><p id="upload-help" class="muted mt-2 text-xs">Minimal 1 gambar, maksimal 5 gambar. JPG, PNG, atau WebP. Maksimal 4 MB per gambar, total maksimal 20 MB. Anda dapat memilih tambahan gambar secara bertahap.</p><p data-upload-summary class="muted mt-2 text-xs" aria-live="polite"></p><div data-upload-previews class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"></div></div>
        <div data-trade-feedback class="hidden lg:col-span-2 rounded-xl border border-white/15 p-4 text-sm whitespace-pre-line" role="status" tabindex="-1"></div>
        <div data-upload-progress class="hidden lg:col-span-2"><label class="label" for="trade-progress">Mengirim pengajuan…</label><progress id="trade-progress" max="100" value="0" class="w-full accent-orange-400"></progress><p class="muted text-xs" data-progress-text></p></div>
        <div class="lg:col-span-2 flex flex-wrap gap-3"><button class="btn">Kirim pengajuan ↗</button><a class="btn-secondary" href="{{ route('catalog') }}">Kembali ke katalog</a></div>
    </form>
</section>
@endsection

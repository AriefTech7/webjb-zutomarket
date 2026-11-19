@extends('layouts.admin')
@php($isRentalForm = (bool) old('is_rental', $account->exists ? $account->is_rental : request()->boolean('rental')))
@section('title', ($account->exists ? 'Edit' : 'Tambah').($isRentalForm ? ' akun rental' : ' akun'))
@section('admin-title', ($account->exists ? 'Edit' : 'Tambah').($isRentalForm ? ' akun rental' : ' akun game'))
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
                    @if (auth()->user()->is_super_admin)
                        <a class="mt-2 block text-sm text-lime-300" href="{{ route('admin.categories') }}">Tambahkan kategori
                            terlebih dahulu →</a>
                    @else
                        <p class="muted mt-2 text-xs">Belum ada kategori. Minta super admin menambahkannya.</p>
                    @endif
                @endif
            </div>
            @if ($admins->isNotEmpty())
                {{-- Only the super admin sees this: who owns (and may edit) the account. --}}
                <div><label class="label" for="owner_id">Pemilik akun</label><select class="field" id="owner_id" name="owner_id">
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}" @selected((int) old('owner_id', $account->owner_id ?? auth()->id()) === $admin->id)>{{ $admin->username }} · {{ $admin->name }}</option>
                        @endforeach
                    </select>
                    <p class="muted mt-2 text-xs">Hanya pemilik dan super admin yang bisa mengedit atau menghapus akun ini.</p>
                </div>
            @endif
            @unless ($isRentalForm)
            <div><label class="label" for="price">Harga (Rupiah)</label><input class="field" id="price"
                    name="price" type="text" inputmode="numeric" autocomplete="off" maxlength="17" data-money
                    value="{{ old('price', $account->price) }}" required placeholder="Contoh: 1.000.000"></div>
            <div><label class="label" for="original_price">Harga coret (opsional)</label><input class="field"
                    id="original_price" name="original_price" type="text" inputmode="numeric" autocomplete="off" maxlength="17" data-money
                    value="{{ old('original_price', $account->original_price) }}" placeholder="Harga sebelum diskon">
                <p class="muted mt-2 text-xs">Tampil dicoret di atas harga jual, lengkap dengan persentase diskon.</p>
            </div>
            <div><label class="label" for="flash_sale_ends_at">Flash sale sampai (opsional)</label><input class="field"
                    id="flash_sale_ends_at" name="flash_sale_ends_at" type="datetime-local"
                    value="{{ old('flash_sale_ends_at', $account->flash_sale_ends_at?->timezone(\App\Models\GameAccount::LOCAL_TIMEZONE)->format('Y-m-d\TH:i')) }}">
                <p class="muted mt-2 text-xs">Waktu WIB. Akun tampil di halaman Flash Sale sampai waktu ini. Kosongkan jika tidak ikut flash sale.</p>
            </div>
            @php($storePercent = \App\Models\Setting::downPaymentPercent())
            @php($storeDays = $account->exists ? \App\Models\Setting::dueDaysFor($account->price) : null)
            <div class="rounded-2xl border border-amber-300/25 bg-amber-300/5 p-4 md:col-span-2">
                <p class="text-sm font-bold text-amber-300">Cicilan untuk pengunjung</p>
                <p class="muted mt-1 text-xs">Tampil di halaman akun (DP, sisa bayar, jangka waktu) dan di pesan WhatsApp "Cicilan". Kosongkan untuk mengikuti @if (auth()->user()->is_super_admin)<a class="text-lime-300 hover:underline" href="{{ route('admin.settings') }}#dp-terms">aturan toko</a>@else aturan toko @endif.</p>
                <label class="mt-3 flex cursor-pointer items-center gap-3 text-sm font-semibold text-white">
                    <input type="hidden" name="installment_enabled" value="0">
                    <input type="checkbox" name="installment_enabled" value="1" class="size-4 accent-lime-300" @checked(old('installment_enabled', $account->installment_enabled ?? true))>
                    Akun ini bisa dicicil <span class="font-normal text-slate-400">(label "Bisa cicil" di kartu, kotak cicilan &amp; tombol Cicilan)</span>
                </label>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="installment_dp_percent">DP cicilan (%)</label>
                        <input class="field" id="installment_dp_percent" name="installment_dp_percent" type="number" inputmode="numeric" min="1" max="100"
                            value="{{ old('installment_dp_percent', $account->installment_dp_percent) }}" placeholder="{{ $storePercent ? 'Kosong = '.$storePercent.'% (aturan toko)' : 'Contoh: 30' }}">
                    </div>
                    <div>
                        <label class="label" for="installment_days">Jangka waktu cicilan (hari)</label>
                        <input class="field" id="installment_days" name="installment_days" type="number" inputmode="numeric" min="1" max="365"
                            value="{{ old('installment_days', $account->installment_days) }}" placeholder="{{ $storeDays ? 'Kosong = '.$storeDays.' hari (sesuai harga)' : 'Kosong = sesuai harga akun' }}">
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="label" for="card_note">Catatan di kartu (opsional)</label>
                <input class="field" id="card_note" name="card_note" maxlength="30" value="{{ old('card_note', $account->card_note) }}" placeholder="Contoh: Bisa TT, Nego, Full skin">
                <p class="muted mt-2 text-xs">Label pendek di samping nama kategori pada kartu produk dan halaman akun. Maksimal 30 karakter.</p>
            </div>
            @endunless
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
                @foreach ($isRentalForm ? ['draft' => 'Draft / disembunyikan', 'available' => 'Siap disewa', 'rented' => 'Sedang disewa'] : ['draft' => 'Draft / disembunyikan', 'available' => 'Tersedia', 'dp' => 'Sudah DP', 'cicil' => 'Sudah dicicil', 'sold' => 'Terjual'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $account->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @unless ($isRentalForm)
                <p class="muted mt-2 text-xs">"Sudah DP" dan "Sudah dicicil" tetap tampil di katalog dengan label tersebut, tetapi tidak bisa dibeli. Ubah ke "Terjual" setelah lunas agar masuk data penjualan.</p>
            @endunless
        </div>
        @unless ($isRentalForm)
            @php($dpPercentDefault = \App\Models\Setting::downPaymentPercent())
            <div data-booking-fields class="grid gap-4 rounded-2xl border border-amber-300/25 bg-amber-300/5 p-4 sm:grid-cols-2">
                <p class="text-sm font-bold text-amber-300 sm:col-span-2">Pembayaran DP / cicilan</p>
                <div>
                    <label class="label" for="dp_percent">Sudah dibayar (%)</label>
                    <input class="field" id="dp_percent" name="dp_percent" type="number" inputmode="numeric" min="1" max="100"
                        value="{{ old('dp_percent', $account->dp_percent) }}" placeholder="{{ $dpPercentDefault ? 'Kosong = '.$dpPercentDefault.'%' : 'Contoh: 30' }}">
                    <p class="muted mt-2 text-xs">Persen dari harga jual yang sudah dibayar pembeli. Naikkan setiap ada cicilan masuk.</p>
                </div>
                <div>
                    <label class="label" for="payment_due_on">Jatuh tempo pelunasan</label>
                    <input class="field" id="payment_due_on" name="payment_due_on" type="date" value="{{ old('payment_due_on', $account->payment_due_on?->toDateString()) }}">
                    <p class="muted mt-2 text-xs">Kosong = dihitung dari aturan batas lunas sesuai harga akun (mulai hari disimpan).</p>
                </div>
            </div>
        @endunless
        <input type="hidden" name="is_rental" value="{{ $isRentalForm ? 1 : 0 }}">
        @if ($isRentalForm)
        @php($packageRows = array_pad(array_values(old('packages', $account->rentalPackages->map(fn ($package) => ['duration' => $package->duration, 'price' => $package->price])->all())), 6, ['duration' => '', 'price' => '']))
        <fieldset class="panel space-y-4 p-5">
            <legend class="font-semibold">Paket sewa</legend>
            <p class="muted text-xs">Akun rental tampil di halaman Rental, bukan di katalog jual. Harga akun otomatis memakai paket termurah.</p>
            <div>
                <p class="label">Paket sewa (maksimal 6)</p>
                <div class="space-y-2">
                    @foreach ($packageRows as $index => $package)
                        <div class="grid grid-cols-2 gap-2">
                            <input class="field" name="packages[{{ $index }}][duration]" value="{{ $package['duration'] ?? '' }}" maxlength="50" placeholder="Contoh: 6 Jam" aria-label="Durasi paket {{ $index + 1 }}">
                            <input class="field" name="packages[{{ $index }}][price]" type="text" inputmode="numeric" autocomplete="off" maxlength="17" data-money value="{{ $package['price'] ?? '' }}" placeholder="Harga" aria-label="Harga paket {{ $index + 1 }}">
                        </div>
                    @endforeach
                </div>
            </div>
            <div><label class="label" for="ready_estimate_at">Estimasi ready (WIB, saat sedang disewa)</label><input class="field"
                    id="ready_estimate_at" name="ready_estimate_at" type="datetime-local"
                    value="{{ old('ready_estimate_at', $account->ready_estimate_at?->timezone(\App\Models\GameAccount::LOCAL_TIMEZONE)->format('Y-m-d\TH:i')) }}"></div>
        </fieldset>
        @endif
        <div><label class="label" for="seller_whatsapp">Nomor WhatsApp penjual (opsional)</label><input class="field"
                id="seller_whatsapp" name="seller_whatsapp" inputmode="numeric" maxlength="15"
                value="{{ old('seller_whatsapp', $account->seller_whatsapp) }}" placeholder="6281234567890">
            <p class="muted mt-2 text-xs">Isi jika pembeli akun ini harus langsung menghubungi penjualnya. Kosongkan untuk
                memakai nomor WhatsApp toko. Awali dengan 62, tanpa tanda + atau spasi.</p>
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
        <div class="grid grid-cols-1 gap-3 sm:flex sm:flex-wrap"><button class="btn">Simpan akun</button><a class="btn-secondary"
                href="{{ route(($account->exists ? $account->is_rental : request()->boolean('rental')) ? 'admin.rentals' : 'admin.accounts') }}">Batal</a></div>
    </form>
@endsection

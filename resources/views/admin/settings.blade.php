@extends('layouts.admin')
@section('title', 'Pengaturan WhatsApp')
@section('admin-title', 'Pengaturan toko & WhatsApp')
@section('admin-content')
    <form action="{{ route('admin.settings.update') }}" method="POST" class="panel max-w-2xl space-y-5 p-6">
        @csrf @method('PUT')
        <div>
            <label for="store_name" class="label">Nama toko</label>
            <input class="field" id="store_name" name="store_name" required maxlength="80"
                value="{{ old('store_name', \App\Models\Setting::valueFor('store_name')) }}">
        </div>
        <div>
            <label for="whatsapp_number" class="label">Nomor WhatsApp</label>
            <input class="field" id="whatsapp_number" name="whatsapp_number" type="tel" inputmode="numeric"
                pattern="[1-9][0-9]{7,14}" required
                value="{{ old('whatsapp_number', \App\Models\Setting::valueFor('whatsapp_number')) }}"
                placeholder="6281234567890">
            <p class="muted mt-2 text-xs">Gunakan kode negara, tanpa +, spasi, atau tanda hubung. Contoh: 6281234567890.</p>
        </div>
        <div>
            <label for="whatsapp_rental_number" class="label">Nomor WhatsApp rental (opsional)</label>
            <input class="field" id="whatsapp_rental_number" name="whatsapp_rental_number" type="tel" inputmode="numeric"
                pattern="[1-9][0-9]{7,14}"
                value="{{ old('whatsapp_rental_number', \App\Models\Setting::valueFor('whatsapp_rental_number')) }}"
                placeholder="6281234567890">
            <p class="muted mt-2 text-xs">Pesan sewa akun rental dikirim ke nomor ini. Kosongkan untuk memakai nomor WhatsApp di atas.</p>
        </div>
        <div>
            <label for="whatsapp_default_message" class="label">Pesan otomatis</label>
            <textarea class="field" id="whatsapp_default_message" name="whatsapp_default_message" rows="5" required
                maxlength="1000">{{ old('whatsapp_default_message', \App\Models\Setting::valueFor('whatsapp_default_message', 'Halo admin, saya tertarik dengan akun {nama_akun} seharga {harga}. Apakah masih tersedia? {url}')) }}</textarea>
            <p class="muted mt-2 text-xs">Placeholder: {nama_akun}, {harga}, {url}. Pesan diisi otomatis saat pembeli
                membuka WhatsApp.</p>
        </div>
        <fieldset>
            <legend class="label">Warna tema</legend>
            @php($accent = old('accent_color', \App\Models\Setting::valueFor('accent_color', 'default')))
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (['default' => ['Biru lembut (bawaan)', '#a9c3ff'], 'deepskyblue' => ['DeepSkyBlue', '#00bfff']] as $accentValue => [$accentLabel, $accentHex])
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#a9c3ff]/20 bg-[#080f25] p-3 text-sm has-checked:border-lime-300">
                        <input type="radio" name="accent_color" value="{{ $accentValue }}" class="accent-lime-300" @checked($accent === $accentValue)>
                        <span class="size-6 shrink-0 rounded-full border border-white/20" style="background: {{ $accentHex }}"></span>{{ $accentLabel }}
                    </label>
                @endforeach
            </div>
            <p class="muted mt-2 text-xs">Warna tombol, tautan, teks berjalan, dan aksen di seluruh situs.</p>
        </fieldset>
        <div>
            <label for="running_text" class="label">Teks berjalan</label>
            <textarea class="field" id="running_text" name="running_text" rows="5" maxlength="1000"
                placeholder="Satu pengumuman per baris">{{ old('running_text', \App\Models\Setting::valueFor('running_text', \App\Models\Setting::DEFAULT_RUNNING_TEXT)) }}</textarea>
            <p class="muted mt-2 text-xs">Tampil berjalan di bawah menu atas pada semua halaman pengunjung. Tulis satu pengumuman per baris. Kosongkan untuk menyembunyikan teks berjalan.</p>
        </div>
        <button class="btn">Simpan pengaturan</button>
    </form>
@endsection

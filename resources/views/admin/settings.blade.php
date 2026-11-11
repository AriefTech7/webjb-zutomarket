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
            <label for="feedback_email" class="label">Email kritik &amp; saran</label>
            <input class="field" id="feedback_email" name="feedback_email" type="email" maxlength="120"
                value="{{ old('feedback_email', \App\Models\Setting::valueFor('feedback_email', 'zutomarketcs@gmail.com')) }}">
            <p class="muted mt-2 text-xs">Kritik dan saran dari halaman Akun dikirim ke email ini.</p>
        </div>
        <div>
            <label for="whatsapp_default_message" class="label">Pesan otomatis</label>
            <textarea class="field" id="whatsapp_default_message" name="whatsapp_default_message" rows="5" required
                maxlength="1000">{{ old('whatsapp_default_message', \App\Models\Setting::valueFor('whatsapp_default_message', 'Halo admin, saya tertarik dengan akun {nama_akun} seharga {harga}. Apakah masih tersedia? {url}')) }}</textarea>
            <p class="muted mt-2 text-xs">Placeholder: {nama_akun}, {harga}, {url}. Pesan diisi otomatis saat pembeli
                membuka WhatsApp.</p>
        </div>
        <button class="btn">Simpan pengaturan</button>
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Kritik & saran')
@section('description', 'Kirim kritik dan saran untuk ZutoMarket langsung ke email layanan pelanggan.')
@section('content')
    @php($feedbackEmail = \App\Models\Setting::valueFor('feedback_email', 'zutomarketcs@gmail.com'))
    <section class="shell max-w-2xl py-10">
        <a class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-300 transition hover:text-white" href="{{ route('profile') }}">← Kembali</a>
        <p class="eyebrow mt-4">Akun saya</p>
        <h1 class="mt-3 text-4xl font-black">Kritik &amp; saran<span class="text-lime-300">.</span></h1>
        <p class="muted mt-4">Masukanmu membantu kami melayani lebih baik. Setelah menekan Kirim, aplikasi email akan terbuka dengan pesan yang sudah terisi. Tinggal tekan kirim di sana.</p>
        <form class="panel mt-8 space-y-5 p-6" data-feedback-form data-email="{{ $feedbackEmail }}">
            <div><label class="label" for="feedback-type">Jenis</label><select id="feedback-type" class="field" name="type" required>
                    @foreach (['Kritik', 'Saran', 'Laporan masalah'] as $type)
                        <option>{{ $type }}</option>
                    @endforeach
                </select></div>
            <div><label class="label" for="feedback-name">Nama (opsional)</label><input id="feedback-name" class="field" type="text" name="name" maxlength="50" autocomplete="name" placeholder="Nama kamu"></div>
            <div><label class="label" for="feedback-message">Pesan</label><textarea id="feedback-message" class="field" name="message" rows="6" minlength="5" maxlength="1500" required placeholder="Tulis kritik atau saranmu di sini"></textarea></div>
            <button class="btn w-full sm:w-auto">Kirim lewat email ↗</button>
        </form>
        <noscript><p class="panel mt-4 p-5">Aktifkan JavaScript, atau kirim langsung ke {{ $feedbackEmail }}.</p></noscript>
        <p class="muted mt-4 text-xs">Tidak bisa membuka aplikasi email? Kirim langsung ke <a class="text-lime-300 underline" href="mailto:{{ $feedbackEmail }}">{{ $feedbackEmail }}</a>.</p>
    </section>
@endsection

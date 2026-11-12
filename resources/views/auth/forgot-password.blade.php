@extends('layouts.app')
@section('title', 'Lupa password')
@section('content')
    @php($hasWhatsapp = filled(preg_replace('/\D+/', '', \App\Models\Setting::valueFor('whatsapp_number'))))
    <section class="shell py-8 md:py-12">
        @component('auth.partials.auth-card', ['title' => 'Lupa Password', 'googleLabel' => '', 'google' => false])
            @if ($mailReady)
                <p class="muted mb-5 text-center text-sm leading-6">Masukkan email akunmu. Kami kirim tautan untuk membuat password baru.</p>
                <form method="POST" action="{{ route('password.email') }}" class="space-y-3">
                    @csrf
                    <label class="sr-only" for="email">Email</label>
                    <input id="email" name="email" type="email" class="field min-h-12" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="Email akun">
                    <button class="btn !mt-4 min-h-12 w-full">Kirim tautan reset</button>
                </form>
            @elseif ($hasWhatsapp)
                <p class="muted mb-5 text-center text-sm leading-6">Masukkan email akunmu. Kamu akan diarahkan ke WhatsApp admin, lalu admin mengirim password sementara.</p>
                <form method="POST" action="{{ route('password.whatsapp') }}" class="space-y-3">
                    @csrf
                    <label class="sr-only" for="email">Email</label>
                    <input id="email" name="email" type="email" class="field min-h-12" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="Email akun">
                    <button class="btn !mt-4 min-h-12 w-full">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" /></svg>Minta bantuan via WhatsApp
                    </button>
                </form>
            @else
                <p class="muted text-center text-sm leading-6">Hubungi admin lewat halaman <a class="font-bold text-lime-300 hover:underline" href="{{ route('about') }}">Tentang</a> dan sebutkan email akunmu.</p>
            @endif
            <p class="muted mt-6 text-center">Ingat password? <a class="font-bold text-lime-300 hover:underline" href="{{ route('account.login') }}">Masuk</a></p>
        @endcomponent
    </section>
@endsection

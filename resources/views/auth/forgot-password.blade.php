@extends('layouts.app')
@section('title', 'Lupa password')
@section('content')
    @php($whatsapp = preg_replace('/\D+/', '', \App\Models\Setting::valueFor('whatsapp_number')))
    <section class="shell py-8 md:py-14">
        <div class="panel mx-auto max-w-md p-6 sm:p-8">
            <h1 class="text-2xl font-black md:text-3xl">Lupa Password</h1>
            <p class="muted mt-1 text-sm">Ingat password? <a class="font-bold text-lime-300 hover:underline" href="{{ route('account.login') }}">Masuk</a></p>
            @if ($mailReady)
                <p class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 text-slate-300">Masukkan email akunmu. Kami kirim tautan untuk membuat password baru.</p>
                <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
                    @csrf
                    <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required maxlength="255" autocomplete="email" autofocus></div>
                    <button class="btn w-full rounded-full">Kirim tautan reset</button>
                </form>
            @else
                <p class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 text-slate-300">Reset password lewat email belum aktif. Hubungi admin lewat WhatsApp dan sebutkan email akunmu.</p>
                @if ($whatsapp)
                    <a class="btn mt-6 w-full rounded-full" href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Halo admin, saya lupa password akun ZutoMarket saya.') }}" target="_blank" rel="noopener noreferrer">Hubungi admin via WhatsApp</a>
                @endif
            @endif
        </div>
    </section>
@endsection

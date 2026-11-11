@extends('layouts.app')
@section('title', 'Daftar akun')
@section('content')
    @php($google = \App\Http\Controllers\GoogleAuthController::enabled())
    <section class="shell py-8 md:py-14">
        <div class="panel mx-auto max-w-md p-6 sm:p-8">
            <h1 class="text-2xl font-black md:text-3xl">Daftar Akun Baru</h1>
            <p class="muted mt-1 text-sm">Sudah punya akun? <a class="font-bold text-lime-300 hover:underline" href="{{ route('account.login') }}">Masuk</a></p>
            <p class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 text-slate-300">Buat akun untuk menyimpan wishlist dan data pemesanan. Pembelian tetap dilanjutkan lewat WhatsApp admin.</p>
            @include('auth.partials.divider', ['text' => $google ? 'Atau daftar dengan email' : 'Daftar dengan email'])
            <form method="POST" action="{{ route('account.register') }}" class="space-y-5">
                @csrf
                <div><label class="label" for="name">Nama</label><input id="name" name="name" type="text" class="field" value="{{ old('name') }}" required maxlength="50" autocomplete="name" autofocus></div>
                <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></div>
                <div>
                    <label class="label" for="password">Password</label>
                    @include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8])
                    <p class="muted mt-1.5 text-xs">Minimal 8 karakter</p>
                </div>
                <div>
                    <label class="label" for="password_confirmation">Konfirmasi password</label>
                    @include('auth.partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8])
                </div>
                <button class="btn w-full rounded-full">Daftar</button>
            </form>
            @include('auth.partials.google', ['divider' => 'Atau daftar dengan Google', 'label' => 'Daftar dengan Google'])
        </div>
    </section>
@endsection

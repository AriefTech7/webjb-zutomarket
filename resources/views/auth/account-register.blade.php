@extends('layouts.app')
@section('title', 'Daftar akun')
@section('content')
    <section class="shell py-8 md:py-12">
        @component('auth.partials.auth-card', ['title' => 'Daftar', 'googleLabel' => 'Daftar dengan Google'])
            <form method="POST" action="{{ route('account.register') }}" class="space-y-3">
                @csrf
                <label class="sr-only" for="name">Nama</label>
                <input id="name" name="name" type="text" class="field min-h-12" value="{{ old('name') }}" required maxlength="50" autocomplete="name" placeholder="Nama">
                <label class="sr-only" for="email">Email</label>
                <input id="email" name="email" type="email" class="field min-h-12" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="Email">
                <label class="sr-only" for="password">Password, minimal 8 karakter</label>
                @include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8, 'placeholder' => 'Password (minimal 8 karakter)'])
                <label class="sr-only" for="password_confirmation">Konfirmasi password</label>
                @include('auth.partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8, 'placeholder' => 'Konfirmasi password'])
                <button class="btn !mt-4 min-h-12 w-full">Daftar</button>
            </form>
            <p class="muted mt-4 text-center text-xs">Dengan mendaftar, kamu menyetujui <a class="font-bold text-lime-300 hover:underline" href="{{ route('privacy') }}">Kebijakan Privasi</a> ZutoMarket.</p>
            <p class="muted mt-6 text-center">Sudah punya akun? <a class="font-bold text-lime-300 hover:underline" href="{{ route('account.login') }}">Masuk</a></p>
        @endcomponent
    </section>
@endsection

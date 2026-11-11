@extends('layouts.app')
@section('title', 'Buat password baru')
@section('content')
    <section class="shell py-8 md:py-14">
        <div class="panel mx-auto max-w-md p-6 sm:p-8">
            <h1 class="text-2xl font-black md:text-3xl">Buat Password Baru</h1>
            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="field" value="{{ old('email', $email) }}" required maxlength="255" autocomplete="email"></div>
                <div>
                    <label class="label" for="password">Password baru</label>
                    @include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8])
                    <p class="muted mt-1.5 text-xs">Minimal 8 karakter</p>
                </div>
                <div>
                    <label class="label" for="password_confirmation">Konfirmasi password</label>
                    @include('auth.partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8])
                </div>
                <button class="btn w-full rounded-full">Simpan password</button>
            </form>
        </div>
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Ubah password')
@section('content')
    @php($googleOnly = filled(auth()->user()->google_id))
    <x-account-frame title="Ubah Password" :back="route('profile')">
        @if ($googleOnly)
            <p class="muted mt-5 text-center text-sm">Akunmu terhubung dengan Google. Buat password di sini kalau ingin bisa masuk dengan email juga.</p>
        @endif
        <form method="POST" action="{{ route('account.password') }}" class="mt-5 space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="label" for="current_password">Password saat ini{{ $googleOnly ? ' (kosongkan kalau belum pernah dibuat)' : '' }}</label>
                @include('auth.partials.password-field', ['id' => 'current_password', 'name' => 'current_password', 'autocomplete' => 'current-password', 'optional' => $googleOnly])
            </div>
            <div>
                <label class="label" for="password">Password baru</label>
                @include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8])
                <p class="muted mt-1.5 text-xs">Minimal 8 karakter</p>
            </div>
            <div>
                <label class="label" for="password_confirmation">Konfirmasi password baru</label>
                @include('auth.partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8])
            </div>
            <button class="btn w-full rounded-full">Simpan password</button>
        </form>
    </x-account-frame>
@endsection

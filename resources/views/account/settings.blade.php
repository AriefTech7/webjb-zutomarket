@extends('layouts.app')
@section('title', 'Pengaturan akun')
@section('content')
    <x-account-frame title="Pengaturan" :back="route('profile')">
        <p class="muted mt-5 text-center text-sm">Nama dan nomor WhatsApp otomatis ditambahkan ke pesan saat kamu memesan.</p>
        <form method="POST" action="{{ route('account.profile') }}" class="mt-5 space-y-5">
            @csrf @method('PUT')
            <div><label class="label" for="account-email">Email</label><input id="account-email" class="field opacity-70" type="email" value="{{ auth()->user()->email }}" disabled></div>
            <div><label class="label" for="account-name">Nama</label><input id="account-name" class="field" type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="50" autocomplete="name"></div>
            <div><label class="label" for="account-whatsapp">Nomor WhatsApp</label><input id="account-whatsapp" class="field" type="tel" name="whatsapp" value="{{ old('whatsapp', auth()->user()->whatsapp) }}" maxlength="20" autocomplete="tel" placeholder="08xxxxxxxxxx"></div>
            <button class="btn w-full rounded-full">Simpan</button>
        </form>
    </x-account-frame>
@endsection

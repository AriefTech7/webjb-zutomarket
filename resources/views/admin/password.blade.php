@extends('layouts.admin')
@section('title', 'Ganti password')
@section('admin-title', 'Ganti password')
@section('admin-content')
    <form method="POST" action="{{ route('admin.password.update') }}" class="panel max-w-xl space-y-5 p-6">
        @csrf @method('PUT')
        <p class="muted">Login sebagai <strong class="text-white">{{ auth()->user()->username }}</strong>. Setelah diganti, login di perangkat lain akan keluar otomatis.</p>
        <div><label class="label" for="current_password">Password saat ini</label>@include('auth.partials.password-field', ['id' => 'current_password', 'name' => 'current_password', 'autocomplete' => 'current-password'])</div>
        <div><label class="label" for="password">Password baru</label>@include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8])
            <p class="muted mt-2 text-xs">Minimal 8 karakter. Pakai kombinasi huruf dan angka.</p></div>
        <div><label class="label" for="password_confirmation">Ulangi password baru</label>@include('auth.partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8])</div>
        <button class="btn">Simpan password</button>
    </form>
@endsection

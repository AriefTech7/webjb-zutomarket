@extends('layouts.app')
@section('title', 'Login admin')
@section('content')
<div class="shell py-16"><form method="POST" action="{{ route('login') }}" class="panel mx-auto max-w-md space-y-5 p-7 md:p-9">@csrf<div><p class="eyebrow">Admin area</p><h1 class="mt-3 text-3xl font-bold">Selamat datang kembali.</h1><p class="muted mt-3">Masuk untuk mengelola katalog toko.</p></div><div><label for="email" class="label">Email</label><input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required autocomplete="username" autofocus></div><div><label for="password" class="label">Password</label><input id="password" name="password" type="password" class="field" required autocomplete="current-password"></div><button class="btn w-full">Masuk ke dashboard →</button><p class="muted text-center text-xs">Akses khusus administrator toko.</p></form></div>
@endsection

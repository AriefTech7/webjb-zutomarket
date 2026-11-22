@extends('layouts.admin')
@section('title', 'Profil & password')
@section('admin-title', 'Profil & password')
@section('admin-content')
    @php($me = auth()->user())
    <div class="grid max-w-3xl gap-6">
        <section class="panel p-6" aria-labelledby="avatar-title">
            <h2 id="avatar-title" class="text-lg font-black">Foto profil</h2>
            <p class="muted mt-1">Tampil di header Live Chat untuk pembeli saat kamu online, dan di pojok kanan atas dashboard. Foto otomatis dipotong persegi.</p>
            <div class="mt-5 flex flex-wrap items-center gap-5">
                <form method="POST" action="{{ route('admin.avatar.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-5">
                    @csrf
                    <span class="relative block size-24 shrink-0 overflow-hidden rounded-full ring-2 ring-lime-300/40">
                        @if ($me->avatarUrl())
                            <img data-avatar-preview src="{{ $me->avatarUrl() }}" alt="Foto profil kamu" class="h-full w-full object-cover">
                        @else
                            <img data-avatar-preview alt="Pratinjau foto profil" class="h-full w-full object-cover" hidden>
                            <span data-avatar-initial class="flex h-full w-full items-center justify-center text-3xl font-black text-white" style="background: hsl({{ ($me->id * 67) % 360 }} 70% 50%)">{{ $me->initial() }}</span>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <label class="label" for="avatar">Pilih foto</label>
                        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required data-avatar-input
                            class="block w-full max-w-xs text-sm text-slate-300 file:mr-3 file:cursor-pointer file:rounded-full file:border-0 file:bg-lime-300 file:px-4 file:py-2 file:text-sm file:font-bold file:text-slate-950">
                        <button class="btn mt-3">Simpan foto</button>
                    </div>
                </form>
                @if ($me->avatar_path)
                    <form method="POST" action="{{ route('admin.avatar.destroy') }}" data-confirm="Hapus foto profil?">
                        @csrf @method('DELETE')
                        <button class="btn-secondary text-rose-300">Hapus foto</button>
                    </form>
                @endif
            </div>
        </section>

        <form method="POST" action="{{ route('admin.password.update') }}" class="panel space-y-5 p-6">
            @csrf @method('PUT')
            <div>
                <h2 class="text-lg font-black">Ganti password</h2>
                <p class="muted mt-1">Login sebagai <strong class="text-white">{{ $me->username }}</strong>. Setelah diganti, login di perangkat lain akan keluar otomatis.</p>
            </div>
            <div><label class="label" for="current_password">Password saat ini</label>@include('auth.partials.password-field', ['id' => 'current_password', 'name' => 'current_password', 'autocomplete' => 'current-password'])</div>
            <div><label class="label" for="password">Password baru</label>@include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8])
                <p class="muted mt-2 text-xs">Minimal 8 karakter. Pakai kombinasi huruf dan angka.</p></div>
            <div><label class="label" for="password_confirmation">Ulangi password baru</label>@include('auth.partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8])</div>
            <button class="btn">Simpan password</button>
        </form>
    </div>
@endsection

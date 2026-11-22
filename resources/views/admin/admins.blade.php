@extends('layouts.admin')
@section('title', 'Akun admin')
@section('admin-title', 'Akun admin')
@section('admin-content')
    <p class="muted mb-5"><strong class="text-white">Admin</strong> hanya bisa melihat dan mengedit akun game miliknya sendiri. <strong class="text-white">Super admin</strong> bisa mengubah semua data toko dan mengatur admin. Buat akun admin hanya untuk orang yang dipercaya. Admin baru login di <strong>/admin/login</strong> memakai username dan password.</p>

    <details class="mb-8" @if ($errors->hasAny(['name', 'username', 'email', 'password']) || old('username')) open @endif>
        <summary class="btn inline-flex cursor-pointer list-none [&::-webkit-details-marker]:hidden">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14" /></svg>Buat akun admin
        </summary>
        <form method="POST" action="{{ route('admin.admins.store') }}" class="panel mt-4 grid gap-4 p-5 md:grid-cols-2 md:p-6">
            @csrf
            <div><label class="label" for="admin-name">Nama</label><input id="admin-name" class="field" name="name" value="{{ old('name') }}" maxlength="50" required autocomplete="off" placeholder="Contoh: Admin Rental"></div>
            <div><label class="label" for="admin-username">Username</label><input id="admin-username" class="field" name="username" value="{{ old('username') }}" minlength="3" maxlength="50" required autocomplete="off" placeholder="Contoh: admin2">
                <p class="muted mt-2 text-xs">Huruf, angka, - atau _. Dipakai untuk login.</p></div>
            <div class="md:col-span-2"><label class="label" for="admin-email">Email</label><input id="admin-email" class="field" type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="off" placeholder="admin2@contoh.com"></div>
            <div><label class="label" for="admin-password">Password admin baru</label>@include('auth.partials.password-field', ['id' => 'admin-password', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8])
                <p class="muted mt-2 text-xs">Minimal 8 karakter.</p></div>
            <div><label class="label" for="admin-password-confirmation">Ulangi password</label>@include('auth.partials.password-field', ['id' => 'admin-password-confirmation', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8])</div>
            <div class="md:col-span-2 rounded-xl border border-amber-300/30 bg-amber-300/5 p-4">
                <label class="label" for="admin-current-password">Password kamu (konfirmasi)</label>
                @include('auth.partials.password-field', ['id' => 'admin-current-password', 'name' => 'current_password', 'autocomplete' => 'current-password'])
                <p class="muted mt-2 text-xs">Untuk memastikan yang membuat akun ini memang kamu.</p>
            </div>
            <div class="md:col-span-2"><button class="btn">Buat akun admin</button></div>
        </form>
    </details>

    <h2 class="mb-3 text-lg font-bold">Daftar admin ({{ $admins->count() }})</h2>
    <div class="space-y-3">
        @foreach ($admins as $admin)
            <div class="panel flex flex-wrap items-center justify-between gap-4 p-4 md:p-5">
                <div class="flex min-w-0 items-center gap-3">
                @include('layouts.avatar', ['user' => $admin, 'class' => 'size-11 shrink-0 rounded-full text-base', 'alt' => 'Foto '.$admin->username])
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-1.5 font-bold text-white">{{ $admin->username }}
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase {{ $admin->is_super_admin ? 'bg-amber-300/15 text-amber-300' : 'bg-white/10 text-slate-300' }}">{{ $admin->is_super_admin ? 'Super admin' : 'Admin' }}</span>
                        @if ($admin->is(auth()->user()))<span class="rounded-full bg-lime-300/15 px-2 py-0.5 text-[10px] font-black uppercase text-lime-300">Kamu</span>@endif</p>
                    <p class="text-xs text-slate-400">{{ $admin->name }} · {{ $admin->email }} · dibuat {{ $admin->created_at?->format('d/m/Y') }} · {{ $admin->owned_accounts_count }} akun game</p>
                </div>
                </div>
                @php($bag = $errors->getBag('admin'.$admin->id))
                @if ($bag->any())
                    <ul class="w-full rounded-xl border border-rose-400/30 bg-rose-400/10 p-3 text-sm text-rose-200">
                        @foreach ($bag->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                @endif
                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    <details class="w-full sm:w-auto" @if ($bag->hasAny(['name', 'username', 'email']) || ($bag->has('current_password') && old('username'))) open @endif>
                        <summary class="btn-secondary cursor-pointer list-none [&::-webkit-details-marker]:hidden">Edit</summary>
                        <form method="POST" action="{{ route('admin.admins.update', $admin) }}" class="mt-3 grid gap-3 sm:w-96">
                            @csrf @method('PUT')
                            <div><label class="label text-xs" for="edit-name-{{ $admin->id }}">Nama</label><input id="edit-name-{{ $admin->id }}" class="field" name="name" value="{{ old('name', $admin->name) }}" maxlength="50" required></div>
                            <div><label class="label text-xs" for="edit-username-{{ $admin->id }}">Username (untuk login)</label><input id="edit-username-{{ $admin->id }}" class="field" name="username" value="{{ old('username', $admin->username) }}" minlength="3" maxlength="50" required autocomplete="off"></div>
                            <div><label class="label text-xs" for="edit-email-{{ $admin->id }}">Email</label><input id="edit-email-{{ $admin->id }}" class="field" type="email" name="email" value="{{ old('email', $admin->email) }}" maxlength="255" required autocomplete="off"></div>
                            <div><label class="label text-xs" for="edit-current-{{ $admin->id }}">Password kamu (konfirmasi)</label><input id="edit-current-{{ $admin->id }}" class="field" type="password" name="current_password" required autocomplete="current-password"></div>
                            <button class="btn">Simpan</button>
                        </form>
                    </details>
                    @if ($admin->is(auth()->user()))
                        <a class="btn-secondary w-full sm:w-auto" href="{{ route('admin.password') }}">Ganti password</a>
                    @else
                        <details class="w-full sm:w-auto" @if ($bag->has('new_password') || ($bag->has('current_password') && ! old('username'))) open @endif>
                            <summary class="btn-secondary cursor-pointer list-none [&::-webkit-details-marker]:hidden">Ganti password</summary>
                            <form method="POST" action="{{ route('admin.admins.password', $admin) }}" class="mt-3 grid gap-3 sm:w-96">
                                @csrf @method('PUT')
                                <div><label class="label text-xs" for="new-pass-{{ $admin->id }}">Password baru untuk {{ $admin->username }}</label><input id="new-pass-{{ $admin->id }}" class="field" type="password" name="new_password" minlength="8" required autocomplete="new-password"></div>
                                <div><label class="label text-xs" for="new-pass2-{{ $admin->id }}">Ulangi password baru</label><input id="new-pass2-{{ $admin->id }}" class="field" type="password" name="new_password_confirmation" minlength="8" required autocomplete="new-password"></div>
                                <div><label class="label text-xs" for="pass-current-{{ $admin->id }}">Password kamu (konfirmasi)</label><input id="pass-current-{{ $admin->id }}" class="field" type="password" name="current_password" required autocomplete="current-password"></div>
                                <button class="btn">Simpan password</button>
                                <p class="muted text-xs">{{ $admin->username }} akan keluar dari perangkat lain dan harus login dengan password baru.</p>
                            </form>
                        </details>
                    @endif
                @unless ($admin->is(auth()->user()))
                    <details class="w-full sm:w-auto">
                        <summary class="btn-secondary cursor-pointer list-none [&::-webkit-details-marker]:hidden">{{ $admin->is_super_admin ? 'Jadikan admin biasa' : 'Jadikan super admin' }}</summary>
                        <form method="POST" action="{{ route('admin.admins.role', $admin) }}" class="mt-3 flex flex-wrap items-end gap-2">
                            @csrf @method('PATCH')
                            <div class="min-w-48 flex-1"><label class="label text-xs" for="role-current-{{ $admin->id }}">Password kamu</label>
                                <input id="role-current-{{ $admin->id }}" class="field" type="password" name="current_password" required autocomplete="current-password"></div>
                            <button class="btn-secondary">Simpan peran</button>
                        </form>
                    </details>
                    <details class="w-full sm:w-auto">
                        <summary class="btn-secondary cursor-pointer list-none text-rose-300 [&::-webkit-details-marker]:hidden">Hapus</summary>
                        <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" class="mt-3 flex flex-wrap items-end gap-2">
                            @csrf @method('DELETE')
                            <div class="min-w-48 flex-1"><label class="label text-xs" for="delete-current-{{ $admin->id }}">Password kamu</label>
                                <input id="delete-current-{{ $admin->id }}" class="field" type="password" name="current_password" required autocomplete="current-password"></div>
                            <button class="btn-secondary text-rose-300">Hapus {{ $admin->username }}</button>
                        </form>
                        <p class="muted mt-2 text-xs">Akun game milik {{ $admin->username }} dipindahkan ke kamu.</p>
                    </details>
                @endunless
                </div>
            </div>
        @endforeach
    </div>
@endsection

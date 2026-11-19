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
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-1.5 font-bold text-white">{{ $admin->username }}
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase {{ $admin->is_super_admin ? 'bg-amber-300/15 text-amber-300' : 'bg-white/10 text-slate-300' }}">{{ $admin->is_super_admin ? 'Super admin' : 'Admin' }}</span>
                        @if ($admin->is(auth()->user()))<span class="rounded-full bg-lime-300/15 px-2 py-0.5 text-[10px] font-black uppercase text-lime-300">Kamu</span>@endif</p>
                    <p class="text-xs text-slate-400">{{ $admin->name }} · {{ $admin->email }} · dibuat {{ $admin->created_at?->format('d/m/Y') }} · {{ $admin->owned_accounts_count }} akun game</p>
                </div>
                @unless ($admin->is(auth()->user()))
                    <div class="flex w-full flex-wrap gap-2 sm:w-auto">
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
                    </div>
                @endunless
            </div>
        @endforeach
    </div>
@endsection

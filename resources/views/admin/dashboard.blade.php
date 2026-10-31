@extends('layouts.admin')
@section('title', 'Dashboard admin')
@section('admin-content')
    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (['Total akun' => [$total, '◫'], 'Kategori game' => [$categories, '◇'], 'Akun tersedia' => [$available, '●'], 'Akun terjual' => [$sold, '✓']] as $label => [$value, $icon])
            <div class="panel relative overflow-hidden p-6"><span class="absolute right-5 top-4 text-2xl text-lime-300/30"
                    aria-hidden="true">{{ $icon }}</span>
                <p class="muted">{{ $label }}</p>
                <p class="mt-3 text-4xl font-black text-lime-300">{{ $value }}</p>
            </div>
        @endforeach
    </div>
    @if (!\App\Models\Setting::valueFor('whatsapp_number'))
        <div class="panel mb-7 p-5 text-sm text-amber-200">Nomor WhatsApp belum diatur. <a class="underline"
                href="{{ route('admin.settings') }}">Atur sekarang</a> agar pembeli dapat menghubungi toko.</div>
    @endif
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">Akun terbaru</h2>
            <p class="muted mt-1 text-xs">Lima akun yang terakhir ditambahkan.</p>
        </div><a class="btn" href="{{ route('admin.accounts.create') }}">+ Tambah akun</a>
    </div>@include('admin.account-table')
@endsection

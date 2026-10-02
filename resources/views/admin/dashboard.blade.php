@extends('layouts.admin')
@section('title', 'Dashboard admin')
@section('admin-content')
<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">@foreach(['Total akun' => $total, 'Kategori game' => $categories, 'Akun tersedia' => $available, 'Akun terjual' => $sold, 'Tukar tambah pending' => $tradeRequests] as $label => $value)<div class="panel p-6"><p class="muted">{{ $label }}</p><p class="mt-3 text-4xl font-black text-lime-300">{{ $value }}</p></div>@endforeach</div>
@if(!\App\Models\Setting::valueFor('whatsapp_number'))<div class="panel mb-7 p-5 text-sm text-amber-200">Nomor WhatsApp belum diatur. <a class="underline" href="{{ route('admin.settings') }}">Atur sekarang</a> agar pembeli dapat menghubungi toko.</div>@endif
<div class="mb-5 flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-bold">Akun terbaru</h2><a class="btn" href="{{ route('admin.accounts.create') }}">+ Tambah akun</a></div>@include('admin.account-table')
@endsection

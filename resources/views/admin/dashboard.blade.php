@extends('layouts.admin')
@section('title', 'Dashboard admin')
@section('admin-content')
    <div class="mb-6 grid grid-cols-2 gap-3 md:mb-8 md:gap-4 xl:grid-cols-4">
        @foreach (['Total akun' => [$total, 'gamepad', 'border-lime-300/20 bg-lime-300/10 text-lime-300'], 'Kategori game' => [$categories, 'grid', 'border-violet-300/20 bg-violet-300/10 text-violet-300'], 'Akun tersedia' => [$available, 'check', 'border-emerald-300/20 bg-emerald-300/10 text-emerald-300'], 'Akun terjual' => [$sold, 'bag', 'border-amber-300/20 bg-amber-300/10 text-amber-300']] as $label => [$value, $icon, $iconClasses])
            <div class="panel min-w-0 p-4 md:p-6">
                <div class="flex items-start justify-between gap-2 md:gap-3">
                    <p class="muted min-h-10 text-xs leading-5 md:pt-2 md:text-sm">{{ $label }}</p>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border md:h-11 md:w-11 {{ $iconClasses }}"
                        aria-hidden="true">
                        <svg class="h-5 w-5 md:h-6 md:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            @switch($icon)
                                @case('gamepad')
                                    <path
                                        d="M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7Z" />
                                    <path d="M7.5 10v5M5 12.5h5" />
                                    <circle cx="16" cy="11" r=".75" fill="currentColor" stroke="none" />
                                    <circle cx="18" cy="14" r=".75" fill="currentColor" stroke="none" />
                                @break

                                @case('grid')
                                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                @break

                                @case('check')
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="m8 12 2.5 2.5L16 9" />
                                @break

                                @case('bag')
                                    <path d="M5 7h14l1 14H4L5 7ZM9 7V6a3 3 0 0 1 6 0v1" />
                                    <path d="m8.5 14 2.5 2.5 4.5-4.5" />
                                @break
                            @endswitch
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-black text-lime-300 md:text-4xl">{{ $value }}</p>
            </div>
        @endforeach
    </div>
    @if (!\App\Models\Setting::valueFor('whatsapp_number'))
        <div class="panel mb-7 p-5 text-sm text-amber-200">Nomor WhatsApp belum diatur. <a class="underline"
                href="{{ route('admin.settings') }}">Atur sekarang</a> agar pembeli dapat menghubungi toko.</div>
    @endif
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold">Akun terbaru</h2>
        </div><a class="btn w-full sm:w-auto" href="{{ route('admin.accounts.create') }}">+ Tambah akun</a>
    </div>@include('admin.account-table')
@endsection

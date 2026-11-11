@extends('layouts.admin')
@section('title', 'Rental akun')
@section('admin-title', 'Rental akun')
@section('admin-content')
    <form class="mb-5 flex flex-col gap-3 sm:flex-row" method="GET" action="{{ route('admin.rentals') }}" role="search">
        <div class="min-w-0 flex-1"><label class="sr-only" for="rental-search">Cari akun rental</label><input id="rental-search"
                class="field" type="search" name="q" value="{{ request('q') }}" maxlength="100"
                placeholder="Cari judul, game, atau spesifikasi..."></div>
        <button class="btn w-full sm:w-auto">Cari</button>
        @if (request()->filled('q'))
            <a class="btn-secondary w-full sm:w-auto" href="{{ route('admin.rentals') }}">Reset</a>
        @endif
    </form>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="muted min-w-0 break-words">
            @if (request()->filled('q'))
                {{ $accounts->total() }} akun rental cocok dengan "{{ request('q') }}"
            @else
                {{ $readyCount }} siap disewa · {{ $rentedCount }} sedang disewa
            @endif
        </p><a class="btn w-full sm:w-auto" href="{{ route('admin.accounts.create', ['rental' => 1]) }}">+ Tambah akun rental</a>
    </div>
    <ul class="space-y-3" aria-label="Daftar akun rental">
        @forelse ($accounts as $account)
            <li class="panel p-4 md:p-5">
                <div class="flex items-start gap-3">
                    <img class="h-14 w-16 shrink-0 rounded-lg object-cover" src="{{ $account->imageUrl() }}" alt="" loading="lazy">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="break-words font-semibold">{{ $account->title }}</p>
                            <span class="status {{ ['available' => 'text-lime-300', 'rented' => 'text-amber-300', 'draft' => 'text-slate-400', 'sold' => 'text-rose-300'][$account->status] }}">{{ ['available' => 'Siap disewa', 'rented' => 'Sedang disewa', 'draft' => 'Draft', 'sold' => 'Terjual'][$account->status] }}</span>
                        </div>
                        <p class="muted text-xs">{{ $account->category->name }}
                            @if ($account->status === 'rented' && $account->ready_estimate_at)
                                · ready ± {{ $account->ready_estimate_at->timezone(\App\Models\GameAccount::LOCAL_TIMEZONE)->format('d M H:i') }} WIB
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-slate-400">{{ $account->rentalPackages->map(fn ($package) => $package->duration.' '.$package->formattedPrice())->join(' · ') ?: 'Belum ada paket' }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-3 border-t border-white/10 pt-3 lg:flex-row lg:items-end lg:justify-between">
                    @if ($account->status === 'available')
                        <form method="POST" action="{{ route('admin.rentals.status', $account) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="rented">
                            <div><label class="label text-xs" for="ready-{{ $account->id }}">Estimasi ready (WIB, opsional)</label><input class="field" id="ready-{{ $account->id }}" type="datetime-local" name="ready_estimate_at"></div>
                            <button class="btn">Tandai sedang disewa</button>
                        </form>
                    @elseif ($account->status === 'rented')
                        <form method="POST" action="{{ route('admin.rentals.status', $account) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="available">
                            <button class="btn">Tandai siap disewa</button>
                        </form>
                    @else
                        <p class="muted text-xs">Ubah status lewat Edit untuk menampilkan akun ini.</p>
                    @endif
                    <div class="flex items-center gap-4">
                        <a class="py-2 text-sm text-lime-300" href="{{ route('admin.accounts.edit', $account) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" data-confirm="Hapus akun rental ini beserta gambarnya?">
                            @csrf @method('DELETE')
                            <button class="py-2 text-sm text-rose-300">Hapus</button>
                        </form>
                    </div>
                </div>
            </li>
        @empty
            <li class="panel p-10 text-center text-sm text-slate-400">{{ request()->filled('q') ? 'Tidak ada akun rental yang cocok dengan pencarian ini.' : 'Belum ada akun rental. Tambahkan akun rental pertamamu.' }}</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $accounts->links() }}</div>
@endsection

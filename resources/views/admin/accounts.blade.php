@extends('layouts.admin')
@section('title', 'Kelola akun game')
@section('admin-title', 'Kelola akun game')
@section('admin-content')
    <form class="mb-5 flex flex-col gap-3 sm:flex-row" method="GET" action="{{ route('admin.accounts') }}" role="search">
        <div class="min-w-0 flex-1"><label class="sr-only" for="admin-search">Cari akun game</label><input id="admin-search"
                class="field" type="search" name="q" value="{{ request('q') }}" maxlength="100"
                placeholder="Cari judul, game, rank, atau spesifikasi..."></div>
        <button class="btn w-full sm:w-auto">Cari</button>
        @if (request()->filled('q'))
            <a class="btn-secondary w-full sm:w-auto" href="{{ route('admin.accounts') }}">Reset</a>
        @endif
    </form>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="muted min-w-0 break-words">
            @if (request()->filled('q'))
                {{ $accounts->total() }} akun cocok dengan "{{ request('q') }}"
            @else
                {{ $accounts->total() }} akun dalam katalog
            @endif
        </p><a class="btn w-full sm:w-auto" href="{{ route('admin.accounts.create') }}">+ Tambah akun</a>
    </div>
    @if ($accounts->isNotEmpty())
        <form id="bulk-delete" method="POST" action="{{ route('admin.accounts.destroy-many') }}" data-bulk-delete
            class="panel mb-3 flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            @csrf @method('DELETE')
            <label class="flex items-center gap-3 text-sm font-semibold"><input type="checkbox" class="size-5" data-bulk-all> Pilih semua di halaman ini</label>
            <button class="inline-flex min-h-9 items-center justify-center rounded-lg border border-rose-300/30 bg-rose-300/10 px-4 text-xs font-bold text-rose-300 transition hover:bg-rose-300/20 disabled:cursor-not-allowed disabled:opacity-40 md:min-h-11 md:text-sm"
                data-bulk-submit disabled>Hapus terpilih (<span data-bulk-count>0</span>)</button>
        </form>
    @endif
    @include('admin.account-table', ['bulk' => true, 'emptyMessage' => request()->filled('q') ? 'Tidak ada akun yang cocok dengan pencarian ini.' : null])<div class="mt-6">{{ $accounts->links() }}</div>
@endsection

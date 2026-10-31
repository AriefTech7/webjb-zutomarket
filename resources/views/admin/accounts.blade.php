@extends('layouts.admin')
@section('title', 'Kelola akun game')
@section('admin-title', 'Kelola akun game')
@section('admin-content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="muted">{{ $accounts->total() }} akun dalam katalog</p><a class="btn w-full sm:w-auto"
            href="{{ route('admin.accounts.create') }}">+ Tambah akun</a>
    </div>@include('admin.account-table')<div class="mt-6">{{ $accounts->links() }}</div>
@endsection

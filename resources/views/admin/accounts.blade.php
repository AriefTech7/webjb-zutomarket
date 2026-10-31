@extends('layouts.admin')
@section('title', 'Kelola akun game')
@section('admin-title', 'Kelola akun game')
@section('admin-content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">{{ $accounts->total() }} akun dalam katalog</p><a class="btn"
            href="{{ route('admin.accounts.create') }}">+ Tambah akun</a>
    </div>@include('admin.account-table')<div class="mt-6">{{ $accounts->links() }}</div>
@endsection

@extends('layouts.admin')
@section('title', 'Live chat')
@section('admin-title', 'Live chat')
@section('admin-content')
    <p class="muted mb-5">Chat dari pembeli yang sudah login, lewat tombol chat di pojok kanan bawah website. Percakapan terbaru di paling atas; yang belum dibalas ditandai.</p>
    <div class="space-y-2">
        @forelse ($conversations as $conversation)
            @php($last = $conversation->messages->first())
            @php($unread = $conversation->unreadForAdmin())
            <div class="panel flex items-center gap-2 p-2 pr-3 transition hover:border-lime-300/40 {{ $unread ? 'border-lime-300/50' : '' }}">
            <a href="{{ route('admin.chat.show', $conversation) }}" class="flex min-w-0 flex-1 items-center gap-4 rounded-xl p-2">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-lime-300/15 font-black text-lime-300" aria-hidden="true">{{ mb_strtoupper(mb_substr($conversation->user?->name ?? '?', 0, 1)) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center justify-between gap-3">
                        <strong class="truncate text-white">{{ $conversation->user?->name ?? 'Pembeli dihapus' }}</strong>
                        <span class="shrink-0 text-xs text-slate-400">{{ $conversation->last_message_at?->timezone('Asia/Jakarta')->format('d/m H:i') }}</span>
                    </span>
                    <span class="mt-0.5 flex items-center gap-2 text-sm">
                        <span class="min-w-0 flex-1 truncate {{ $unread ? 'font-semibold text-white' : 'text-slate-400' }}">@if ($last?->from_admin)<span class="text-slate-500">Kamu: </span>@endif{{ $last?->body }}</span>
                        @if ($unread)<span class="shrink-0 rounded-full bg-lime-300 px-2 py-0.5 text-[10px] font-black text-slate-950">{{ $unread }} baru</span>@endif
                    </span>
                </span>
            </a>
            <form method="POST" action="{{ route('admin.chat.destroy', $conversation) }}" data-confirm="Hapus seluruh chat dengan {{ $conversation->user?->name ?? 'pembeli ini' }}? Pesan yang dihapus tidak bisa dikembalikan.">
                @csrf @method('DELETE')
                <button class="flex size-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-400/10 hover:text-rose-300" aria-label="Hapus chat dengan {{ $conversation->user?->name ?? 'pembeli' }}" title="Hapus chat">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6" /></svg>
                </button>
            </form>
            </div>
        @empty
            <p class="panel p-10 text-center text-sm text-slate-400">Belum ada chat dari pembeli.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $conversations->links() }}</div>
@endsection

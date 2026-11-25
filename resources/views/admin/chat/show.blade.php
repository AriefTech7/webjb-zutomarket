@extends('layouts.admin')
@section('title', 'Chat '.($conversation->user?->name ?? ''))
@section('admin-title', 'Live chat')
@section('admin-content')
    @php($buyer = $conversation->user)
    @php($buyerWa = $buyer?->whatsapp ? preg_replace('/^0/', '62', preg_replace('/\D+/', '', $buyer->whatsapp)) : null)
    <a class="mb-4 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-300 hover:text-white" href="{{ route('admin.chat') }}">← Semua chat</a>
    <section class="panel flex h-[max(24rem,calc(100dvh-16rem))] flex-col overflow-hidden" data-chat-admin aria-label="Chat dengan {{ $buyer?->name }}">
        <header class="flex flex-wrap items-center gap-3 border-b border-white/10 p-4">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-lime-300/15 font-black text-lime-300" aria-hidden="true">{{ mb_strtoupper(mb_substr($buyer?->name ?? '?', 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <h2 class="truncate font-black">{{ $buyer?->name ?? 'Pembeli dihapus' }}</h2>
                <p class="truncate text-xs text-slate-400">{{ $buyer?->email }}@if ($buyer?->whatsapp) · {{ $buyer->whatsapp }}@endif</p>
            </div>
            @if ($buyerWa)
                <a class="btn-secondary" href="https://wa.me/{{ $buyerWa }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            @endif
            <form method="POST" action="{{ route('admin.chat.destroy', $conversation) }}" data-confirm="Hapus seluruh chat dengan {{ $buyer?->name ?? 'pembeli ini' }}? Pesan yang dihapus tidak bisa dikembalikan.">
                @csrf @method('DELETE')
                <button class="btn-secondary text-rose-300">Hapus chat</button>
            </form>
        </header>
        <div class="flex min-h-0 flex-1 flex-col" data-chat data-chat-endpoint="{{ route('admin.chat.messages', $conversation) }}" data-chat-send="{{ route('admin.chat.reply', $conversation) }}">
            <ul class="chat-list" data-chat-list aria-live="polite">
                @foreach ($messages as $message)
                    @php($item = $message->toChatArray(true))
                    <li class="chat-msg {{ $item['mine'] ? 'is-mine' : '' }}" data-message-id="{{ $item['id'] }}">
                        <p class="chat-bubble">{{ $item['body'] }}</p>
                        <span class="chat-time">{{ $item['time'] }}</span>
                    </li>
                @endforeach
            </ul>
            @include('layouts.chat-form', ['chatFormId' => 'admin', 'chatDraft' => session('chat_draft')])
        </div>
    </section>
@endsection

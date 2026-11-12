@extends('layouts.admin')
@section('title', 'Pesan masuk')
@section('admin-title', 'Pesan masuk')
@section('admin-content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="muted">Kritik &amp; saran dari halaman <strong>Akun → Kritik &amp; saran</strong>. {{ $unread ? $unread.' pesan belum dibaca.' : 'Semua pesan sudah dibaca.' }}</p>
        @if ($unread)
            <form method="POST" action="{{ route('admin.messages.read-all') }}">@csrf<button class="btn-secondary">Tandai semua dibaca</button></form>
        @endif
    </div>

    @forelse ($messages as $message)
        <article class="panel mb-3 p-5 {{ $message->read_at ? 'opacity-75' : 'border-lime-300/40' }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-2 text-sm">
                        @unless ($message->read_at)<span class="rounded-full bg-lime-300 px-2 py-0.5 text-[10px] font-black uppercase text-slate-950">Baru</span>@endunless
                        <span class="rounded-md border px-2 py-0.5 text-xs font-bold {{ $message->type === 'Laporan masalah' ? 'border-rose-300/40 text-rose-300' : ($message->type === 'Kritik' ? 'border-amber-300/40 text-amber-300' : 'border-emerald-300/40 text-emerald-300') }}">{{ $message->type }}</span>
                        <strong class="text-white">{{ $message->name ?: 'Tanpa nama' }}</strong>
                        @if ($message->user)<span class="text-xs text-slate-400">· akun {{ $message->user->email }}</span>@endif
                    </p>
                    <p class="mt-1 text-xs text-slate-400">{{ $message->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                        @if ($message->contact)
                            · Kontak:
                            @if ($url = $message->whatsappUrl() ?? $message->emailUrl())
                                <a class="text-lime-300 hover:underline" href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $message->contact }}</a>
                            @else
                                {{ $message->contact }}
                            @endif
                        @endif
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <form method="POST" action="{{ route('admin.messages.read', $message) }}">@csrf @method('PATCH')<button class="btn-secondary">{{ $message->read_at ? 'Tandai belum dibaca' : 'Tandai dibaca' }}</button></form>
                    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Hapus pesan ini?">@csrf @method('DELETE')<button class="btn-secondary text-rose-300">Hapus</button></form>
                </div>
            </div>
            <p class="mt-4 whitespace-pre-line break-words text-sm leading-7 text-slate-200">{{ $message->message }}</p>
        </article>
    @empty
        <div class="panel p-10 text-center text-slate-400">Belum ada pesan masuk.</div>
    @endforelse
    <div class="mt-6">{{ $messages->links() }}</div>
@endsection

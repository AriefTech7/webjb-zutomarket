@extends('layouts.app')
@section('title', 'Tentang kami')
@section('description', 'ZutoMarket melayani jual beli akun game dan joki rank Mobile Legends, dengan transaksi melalui WhatsApp admin.')
@section('content')
    @php
        $storeName = \App\Models\Setting::valueFor('store_name');
        $icons = [
            'admin' => 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z',
            'social' => 'M4 4h16v16H4V4ZM16 11.4a4 4 0 1 1-7.9 1.2 4 4 0 0 1 7.9-1.2ZM17.5 6.5h.01',
            'channel' => 'M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM16.2 7.8a6 6 0 0 1 0 8.4M7.8 16.2a6 6 0 0 1 0-8.4M19 5a10 10 0 0 1 0 14M5 19A10 10 0 0 1 5 5',
            'TIKTOK' => 'M9 18V5l12-2v13M9 18a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM21 16a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
            'LINK' => 'M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7',
        ];
        $links = \App\Models\ContactLink::orderBy('sort_order')->orderBy('id')->get()->groupBy('group');
        $groups = collect(\App\Models\ContactLink::GROUPS)->mapWithKeys(fn (string $label, string $group): array => [$group => [
            $label,
            $icons[$group],
            ($links[$group] ?? collect())->map(fn (\App\Models\ContactLink $link): array => [
                $link->title, $link->subtitle, $link->badgeLabel(), $link->url(),
                $group === 'social' ? ($icons[$link->badgeLabel()] ?? ($link->badgeLabel() === 'IG' ? $icons['social'] : $icons['LINK'])) : $icons[$group],
            ])->all(),
        ]])->all();
    @endphp
    <section class="shell max-w-3xl py-8">
        <a class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-300 transition hover:text-white" href="{{ route('catalog') }}">← Kembali</a>
        <div class="panel mt-4 p-6 text-center md:p-10">
            <span class="inline-flex items-center gap-2 rounded-full border border-lime-300/30 bg-lime-300/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-lime-300">✓ Tentang {{ $storeName }}</span>
            <img class="mx-auto mt-6 h-32 w-auto md:h-40" src="{{ asset('images/zuto-logo.webp') }}" alt="Logo {{ $storeName }}" width="480" height="371">
            <h1 class="mt-4 text-2xl font-black uppercase">{{ $storeName }} <span class="text-lime-300" aria-hidden="true">✓</span></h1>
            <p class="muted mx-auto mt-3 max-w-xl">Solusi praktis untuk kebutuhan jual beli akun game. Pilih akun dari katalog atau pesan joki rank Mobile Legends, lalu lanjutkan transaksi langsung dengan admin melalui WhatsApp.</p>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-3" role="tablist" aria-label="Kontak {{ $storeName }}" data-tabs>
            @foreach ($groups as $key => [$label, $icon, $items])
                <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    class="flex min-h-24 flex-col items-center justify-center gap-1 rounded-2xl border border-[#a9c3ff]/15 bg-[#101833]/85 p-3 text-sm font-bold text-slate-300 transition hover:text-white aria-selected:border-lime-300 aria-selected:bg-lime-300 aria-selected:text-slate-950">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg>
                    {{ $label }}
                    <span class="text-xs font-semibold opacity-80">{{ count($items) }}</span>
                </button>
            @endforeach
        </div>

        @foreach ($groups as $key => [$label, $groupIcon, $items])
            <ul id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" class="mt-5 space-y-3" @unless ($loop->first) hidden @endunless>
                @forelse ($items as [$title, $subtitle, $badge, $url, $icon])
                    <li>
                        <a class="group flex items-center gap-4 rounded-2xl border border-[#a9c3ff]/15 bg-[#101833]/85 p-4 transition hover:border-lime-300/40" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-lime-300/10 text-lime-300"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2"><span class="font-bold">{{ $title }}</span><span class="rounded-full bg-lime-300/10 px-2 py-0.5 text-[10px] font-bold text-lime-300">{{ $badge }}</span></span>
                                <span class="muted block break-words">{{ $subtitle }}</span>
                            </span>
                            <span class="text-lime-300 transition group-hover:translate-x-0.5" aria-hidden="true">↗</span>
                        </a>
                    </li>
                @empty
                    <li class="panel p-6 text-center text-sm text-slate-400">Belum ada kontak {{ $label }}.</li>
                @endforelse
            </ul>
        @endforeach
    </section>
@endsection

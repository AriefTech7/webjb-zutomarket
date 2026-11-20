@extends('layouts.app')
@section('title', $report->title)
@section('description', 'Laporan '.mb_strtolower($report->categoryLabel()).' yang sudah dicek admin ZutoMarket.')
@section('content')
    <section class="shell max-w-3xl py-8">
        <a class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-300 transition hover:text-white" href="{{ route('reports') }}">← Semua laporan</a>
        <article class="panel mt-4 p-6 md:p-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-rose-300/30 bg-rose-300/10 px-4 py-1 text-xs font-bold uppercase tracking-wider text-rose-300">{{ $report->categoryLabel() }}</span>
            <h1 class="mt-4 text-2xl font-black leading-tight md:text-3xl">{{ $report->title }}</h1>
            <p class="muted mt-1 text-sm">Ditampilkan {{ $report->approved_at?->locale('id')->translatedFormat('j F Y') }} setelah dicek admin.</p>

            <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                @if ($report->game_id)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4"><dt class="text-xs uppercase tracking-wide text-slate-400">ID game</dt><dd class="mt-1 break-all text-lg font-black text-white">{{ $report->game_id }}</dd></div>
                @endif
                @if ($report->wa_number)
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4"><dt class="text-xs uppercase tracking-wide text-slate-400">No WA pelaku</dt><dd class="mt-1 text-lg font-black text-white">{{ $report->displayWhatsapp() }}</dd></div>
                @endif
            </dl>

            <h2 class="mt-6 font-black">Detail laporan</h2>
            <p class="mt-2 whitespace-pre-line break-words text-sm leading-7 text-slate-300">{{ $report->description }}</p>

            @if ($urls = $report->evidenceUrls())
                <h2 class="mt-6 font-black">Foto bukti ({{ count($urls) }})</h2>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($urls as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-white/10 bg-[#080f25] transition hover:border-lime-300/50">
                            <img class="aspect-square w-full object-cover" src="{{ $url }}" alt="Foto bukti {{ $loop->iteration }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
            @endif

            <p class="mt-6 rounded-xl border border-amber-300/25 bg-amber-300/5 p-4 text-xs leading-5 text-amber-200">Laporan ini dikirim pengunjung dan sudah dicek admin, tetapi tetap bisa keliru. Merasa namamu dilaporkan dengan tidak benar? Hubungi admin melalui halaman <a class="font-bold underline" href="{{ route('about') }}">Tentang</a> untuk banding.</p>
        </article>
    </section>
@endsection

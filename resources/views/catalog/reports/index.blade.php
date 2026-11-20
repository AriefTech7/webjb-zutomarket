@extends('layouts.app')
@section('title', 'Laporan ID & nomor WA bermasalah')
@section('description', 'Cek laporan ID game atau nomor WhatsApp yang bermasalah sebelum bertransaksi, atau kirim laporan baru.')
@section('content')
    @php
        $shieldIcon = 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z';
        $warnIcon = 'M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4M12 17h.01';
    @endphp
    <section class="shell py-5 sm:py-8 md:py-12">
        <div class="panel report-hero relative overflow-hidden p-4 sm:p-6 md:p-9">
            <div class="grid gap-4 sm:gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-lime-300/30 bg-lime-300/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-lime-300 sm:px-4 sm:py-1.5 sm:text-xs">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6ZM14 2v6h6M11.5 17a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM16 19l-2.7-2.7" /></svg>
                        Laporan publik
                    </span>
                    <h1 class="mt-3 max-w-3xl text-xl font-black leading-tight sm:mt-5 sm:text-3xl md:text-5xl">Cari laporan berdasarkan ID game atau nomor WA<span class="text-lime-300">.</span></h1>
                    <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-400 sm:mt-4 sm:text-sm sm:leading-6 md:text-base">Daftar ini hanya menampilkan data laporan non-sensitif yang sudah dicek admin. Nama, email, dan WhatsApp pelapor tetap dilindungi dan hanya bisa dilihat admin.</p>
                </div>
                <dl class="grid grid-cols-3 gap-2 md:gap-3">
                    @foreach ([['Total laporan', $stats['total']], ['ID game', $stats['gameIds']], ['No WA', $stats['numbers']]] as [$statLabel, $statValue])
                        <div class="min-w-0 rounded-xl border border-white/10 bg-white/5 px-2 py-2 text-center sm:min-w-24 sm:rounded-2xl sm:px-3 sm:py-3">
                            <dd class="text-lg font-black leading-tight text-lime-300 sm:text-2xl">{{ $statValue }}</dd>
                            <dt class="mt-0.5 truncate text-[10px] font-semibold text-slate-300 sm:text-[11px]">{{ $statLabel }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>

        <div class="panel mt-5 flex flex-col gap-3 p-3 sm:flex-row sm:items-center">
            <form action="{{ route('reports') }}" method="GET" role="search" class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row">
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only">ID game atau nomor WA pelaku</span>
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input class="field rounded-full py-3 pl-11" type="search" name="q" value="{{ $query }}" maxlength="60" placeholder="Cari ID game atau nomor WA pelaku...">
                </label>
                <button class="btn rounded-full">Cari Laporan</button>
            </form>
            <button type="button" class="btn-secondary rounded-full" data-report-open>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" /></svg>
                Buat Laporan
            </button>
        </div>

        <section class="panel mt-5 p-5 md:p-6" aria-labelledby="report-list-title">
            <span class="inline-flex items-center gap-2 rounded-full border border-rose-300/30 bg-rose-300/10 px-4 py-1 text-xs font-bold uppercase tracking-wider text-rose-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $warnIcon }}" /></svg>
                Database
            </span>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="report-list-title" class="text-2xl font-black">Data Bermasalah</h2>
                    <p class="muted mt-1">{{ $query !== '' ? $reports->total().' laporan cocok dengan "'.$query.'".' : 'Ketuk kartu laporan untuk melihat detail dan foto bukti.' }}</p>
                </div>
                @if ($query !== '')
                    <a class="btn-secondary rounded-full" href="{{ route('reports') }}">Hapus pencarian</a>
                @endif
            </div>

            @if ($reports->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-white/15 p-8 text-center">
                    <svg class="mx-auto h-10 w-10 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $shieldIcon }}M9 12l2 2 4-4" /></svg>
                    <p class="mt-3 font-bold">{{ $query !== '' ? 'Tidak ada laporan untuk "'.$query.'".' : 'Belum ada laporan.' }}</p>
                    <p class="muted mt-1">{{ $query !== '' ? 'Belum ada laporan untuk ID atau nomor ini. Tetap berhati-hati dan selalu bertransaksi lewat admin resmi.' : 'Laporan yang sudah dicek admin akan tampil di sini.' }}</p>
                </div>
            @else
                <ul class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach ($reports as $report)
                        <li class="report-card" style="--i: {{ $loop->index }}">
                            <a href="{{ route('reports.show', $report) }}" class="flex h-full gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-rose-300/40 hover:bg-white/10">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-300/15 text-rose-300" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="{{ $warnIcon }}" /></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-rose-300">{{ $report->categoryLabel() }}</span>
                                    <span class="mt-0.5 block font-black leading-snug text-white">{{ $report->title }}</span>
                                    <span class="mt-2 flex flex-wrap gap-1.5 text-xs">
                                        @if ($report->game_id)<span class="rounded-md bg-white/10 px-2 py-0.5 font-semibold text-slate-200">ID {{ $report->game_id }}</span>@endif
                                        @if ($report->wa_number)<span class="rounded-md bg-white/10 px-2 py-0.5 font-semibold text-slate-200">WA {{ $report->displayWhatsapp() }}</span>@endif
                                    </span>
                                    <span class="mt-2 block text-xs text-slate-400">{{ $report->approved_at?->locale('id')->translatedFormat('j M Y') }} · {{ count($report->evidence ?? []) }} foto bukti</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-5">{{ $reports->links() }}</div>
            @endif
        </section>
    </section>

    @include('catalog.reports.form')
@endsection

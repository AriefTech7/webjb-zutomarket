@extends('layouts.admin')
@section('title', 'Laporan')
@section('admin-title', 'Laporan pengunjung')
@section('admin-content')
    <p class="muted mb-5">Laporan ID game / nomor WA bermasalah dari halaman <a class="text-lime-300 hover:underline" href="{{ route('reports') }}" target="_blank" rel="noopener">/laporan</a>. Laporan baru <strong>baru tampil ke publik setelah kamu tekan "Tampilkan"</strong>. Data pelapor (nama, email, WhatsApp) hanya terlihat di sini.</p>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        @foreach (\App\Models\Report::STATUSES as $statusValue => $statusLabel)
            <a href="{{ route('admin.reports', ['status' => $statusValue]) }}" @if ($status === $statusValue) aria-current="page" @endif
                class="rounded-full border px-4 py-2 text-sm font-bold transition {{ $status === $statusValue ? 'border-lime-300 bg-lime-300 text-slate-950' : 'border-white/10 text-slate-300 hover:text-white' }}">{{ $statusLabel }} ({{ $counts[$statusValue] ?? 0 }})</a>
        @endforeach
        <form method="GET" action="{{ route('admin.reports') }}" class="ml-auto flex w-full gap-2 sm:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            <input class="field py-2" type="search" name="q" value="{{ $query }}" maxlength="60" placeholder="Cari ID game / nomor WA" aria-label="Cari laporan">
            <button class="btn-secondary shrink-0">Cari</button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($reports as $report)
            <article class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-rose-300">{{ $report->categoryLabel() }} · {{ $report->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
                        <h2 class="mt-1 text-lg font-black">{{ $report->title }}</h2>
                        <p class="mt-1 flex flex-wrap gap-2 text-sm">
                            @if ($report->game_id)<span class="rounded-md bg-white/10 px-2 py-0.5 font-semibold">ID {{ $report->game_id }}</span>@endif
                            @if ($report->wa_number)<span class="rounded-md bg-white/10 px-2 py-0.5 font-semibold">WA {{ $report->displayWhatsapp() }}</span>@endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['approved' => 'Tampilkan', 'rejected' => 'Tolak', 'pending' => 'Kembalikan ke antrean'] as $target => $actionLabel)
                            @continue($target === $report->status || ($target === 'pending' && $report->status === 'pending'))
                            <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $target }}">
                                <button class="{{ $target === 'approved' ? 'btn' : 'btn-secondary' }}">{{ $actionLabel }}</button>
                            </form>
                        @endforeach
                        <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" data-confirm="Hapus laporan ini beserta foto buktinya?">
                            @csrf @method('DELETE')
                            <button class="btn-secondary text-rose-300">Hapus</button>
                        </form>
                    </div>
                </div>
                <p class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-slate-300">{{ $report->description }}</p>
                @if ($urls = $report->evidenceUrls())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($urls as $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="block size-20 overflow-hidden rounded-lg border border-white/10"><img class="h-full w-full object-cover" src="{{ $url }}" alt="Bukti {{ $loop->iteration }}" loading="lazy"></a>
                        @endforeach
                    </div>
                @endif
                <div class="mt-3 rounded-xl border border-amber-300/25 bg-amber-300/5 p-3 text-xs text-slate-300">
                    <span class="font-bold text-amber-300">Pelapor (rahasia):</span> {{ $report->reporter_name }} ·
                    <a class="text-lime-300 hover:underline" href="mailto:{{ $report->reporter_email }}">{{ $report->reporter_email }}</a> ·
                    <a class="text-lime-300 hover:underline" href="https://wa.me/{{ $report->reporter_whatsapp }}" target="_blank" rel="noopener noreferrer">WA {{ $report->reporter_whatsapp }}</a>
                </div>
            </article>
        @empty
            <p class="panel p-10 text-center text-sm text-slate-400">Tidak ada laporan {{ mb_strtolower(\App\Models\Report::STATUSES[$status]) }}.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $reports->links() }}</div>
@endsection

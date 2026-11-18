@extends('layouts.admin')
@section('title', 'Dashboard admin')
@section('admin-content')
    @if (!\App\Models\Setting::valueFor('whatsapp_number'))
        <div class="panel mb-5 p-5 text-sm text-amber-200">Nomor WhatsApp belum diatur. <a class="underline"
                href="{{ route('admin.settings') }}">Atur sekarang</a> agar pembeli dapat menghubungi toko.</div>
    @endif

    @php($chartPeak = max(4, collect($chart)->max(fn ($row) => max($row['added'], $row['sold']))))
    @php($chartStep = (int) ceil($chartPeak / 4))
    @php($chartMax = $chartStep * 4)
    @php($rupiahShort = fn (int $amount): string => $amount >= 1000000 ? 'Rp '.rtrim(rtrim(number_format($amount / 1000000, 1, ',', '.'), '0'), ',').' jt' : 'Rp '.number_format($amount, 0, ',', '.'))

    <div class="viz grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem] 2xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="min-w-0 space-y-5">
            {{-- Overview: accounts listed vs sold per day / month. --}}
            <section class="panel p-5 md:p-6" aria-labelledby="overview-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="overview-title" class="text-lg font-black">Ringkasan stok</h2>
                        <p class="mt-1 text-xs text-slate-400">{{ $period === 'bulanan' ? '6 bulan terakhir' : '14 hari terakhir' }} · waktu WIB</p>
                    </div>
                    <nav class="flex items-center gap-1 rounded-full border border-[#a9c3ff]/15 bg-[#0b1230] p-1 text-xs font-bold" aria-label="Periode grafik">
                        @foreach (['harian' => 'Harian', 'bulanan' => 'Bulanan'] as $value => $label)
                            <a href="{{ route('admin.dashboard', $value === 'harian' ? [] : ['periode' => $value]) }}"
                                class="rounded-full px-4 py-1.5 transition {{ $period === $value ? 'bg-lime-300 text-slate-950' : 'text-slate-400 hover:text-white' }}"
                                @if ($period === $value) aria-current="page" @endif>{{ $label }}</a>
                        @endforeach
                    </nav>
                </div>
                <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-300">
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-sm" style="background: var(--viz-added)" aria-hidden="true"></span>Akun masuk</span>
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-sm" style="background: var(--viz-sold)" aria-hidden="true"></span>Terjual</span>
                </div>
                <div class="relative mt-5 h-56 pl-8" aria-hidden="true">
                    @foreach (range(0, 4) as $tick)
                        <div class="absolute left-8 right-0 border-t {{ $tick ? 'border-dashed border-[#a9c3ff]/10' : 'border-[#a9c3ff]/25' }}" style="bottom: {{ $tick * 25 }}%">
                            <span class="absolute -left-8 w-6 -translate-y-1/2 text-right text-[10px] text-slate-500">{{ $tick * $chartStep }}</span>
                        </div>
                    @endforeach
                    <div class="relative flex h-full items-end gap-0.5 md:gap-1">
                        @foreach ($chart as $row)
                            <div class="group relative flex h-full flex-1 items-end justify-center gap-0.5 rounded-t-lg transition hover:bg-white/5">
                                <span class="w-1.5 rounded-t-[4px] sm:w-2.5 md:w-3" style="height: {{ $row['added'] ? max(1.5, $row['added'] / $chartMax * 100) : 0 }}%; background: var(--viz-added)"></span>
                                <span class="w-1.5 rounded-t-[4px] sm:w-2.5 md:w-3" style="height: {{ $row['sold'] ? max(1.5, $row['sold'] / $chartMax * 100) : 0 }}%; background: var(--viz-sold)"></span>
                                <span class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-[#a9c3ff]/20 bg-[#0b1230] px-3 py-2 text-xs shadow-xl shadow-[#020617]/40 group-hover:block">
                                    <span class="block font-bold text-white">{{ $row['title'] }}</span>
                                    <span class="mt-1 flex items-center gap-2 text-slate-300"><span class="h-2 w-2 rounded-sm" style="background: var(--viz-added)"></span>Masuk <strong class="ml-auto pl-3 text-white">{{ $row['added'] }}</strong></span>
                                    <span class="flex items-center gap-2 text-slate-300"><span class="h-2 w-2 rounded-sm" style="background: var(--viz-sold)"></span>Terjual <strong class="ml-auto pl-3 text-white">{{ $row['sold'] }}</strong></span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-2 flex gap-0.5 pl-8 md:gap-1" aria-hidden="true">
                    @foreach ($chart as $row)
                        <span class="min-w-0 flex-1 whitespace-nowrap text-center text-[10px] text-slate-500 {{ $loop->odd || $period === 'bulanan' ? '' : 'invisible sm:visible' }}">{{ $row['label'] }}</span>
                    @endforeach
                </div>
                <table class="sr-only">
                    <caption>Akun masuk dan terjual, {{ $period === 'bulanan' ? '6 bulan terakhir' : '14 hari terakhir' }}</caption>
                    <thead><tr><th scope="col">Periode</th><th scope="col">Akun masuk</th><th scope="col">Terjual</th></tr></thead>
                    <tbody>
                        @foreach ($chart as $row)
                            <tr><th scope="row">{{ $row['title'] }}</th><td>{{ $row['added'] }}</td><td>{{ $row['sold'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <div class="grid gap-5 md:grid-cols-2">
                {{-- Running flash sales. --}}
                <section class="panel flex flex-col p-5" aria-labelledby="flash-title">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="flash-title" class="font-black">Flash sale berjalan</h2>
                        <a href="{{ route('flash-sale') }}" target="_blank" rel="noopener" class="rounded-full bg-amber-300/15 px-3 py-1 text-xs font-bold text-amber-300 hover:bg-amber-300/25">Lihat</a>
                    </div>
                    <ul class="mt-4 space-y-3">
                        @forelse ($flashSales as $flash)
                            <li>
                                <a href="{{ route('admin.accounts.edit', $flash) }}" class="flex items-center gap-3 rounded-xl p-1 transition hover:bg-white/5">
                                    <img src="{{ $flash->imageUrl() }}" alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover object-top" loading="lazy">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-bold text-white">{{ $flash->title }}</span>
                                        <span class="block text-xs text-slate-400">{{ $flash->formattedPrice() }} @if ($flash->hasDiscount())<span class="text-rose-300">-{{ $flash->discountPercent() }}%</span>@endif</span>
                                    </span>
                                    <span class="shrink-0 rounded-lg bg-amber-300 px-2 py-1 text-[10px] font-black text-slate-950">⚡ <span data-countdown="{{ $flash->flash_sale_ends_at->toIso8601ZuluString() }}">{{ $flash->flashSaleCountdown() }}</span></span>
                                </a>
                            </li>
                        @empty
                            <li class="rounded-xl border border-dashed border-[#a9c3ff]/20 p-4 text-center text-sm text-slate-400">Belum ada flash sale yang berjalan.</li>
                        @endforelse
                    </ul>
                </section>

                {{-- Latest feedback messages. --}}
                <section class="panel flex flex-col p-5" aria-labelledby="inbox-title">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="inbox-title" class="font-black">Pesan masuk terbaru</h2>
                        <a href="{{ route('admin.messages') }}" class="rounded-full bg-lime-300/15 px-3 py-1 text-xs font-bold text-lime-300 hover:bg-lime-300/25">Semua</a>
                    </div>
                    <ul class="mt-4 space-y-3">
                        @forelse ($latestMessages as $message)
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $message->read_at ? 'bg-slate-600' : 'bg-lime-300' }}" aria-hidden="true"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2 text-sm">
                                        <strong class="truncate text-white">{{ $message->name ?: 'Tanpa nama' }}</strong>
                                        <span class="shrink-0 text-[10px] text-slate-500">{{ $message->created_at->diffForHumans() }}</span>
                                    </span>
                                    <span class="block truncate text-xs text-slate-400">{{ $message->type }} · {{ \Illuminate\Support\Str::limit($message->message, 80) }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="rounded-xl border border-dashed border-[#a9c3ff]/20 p-4 text-center text-sm text-slate-400">Belum ada kritik &amp; saran yang masuk.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            {{-- Booked accounts: store DP terms, then per account how much is paid, what is left and when it is due. All editable here. --}}
            @php($overdue = $bookings->filter(fn ($booking) => ($booking->daysUntilPaymentDue() ?? 0) < 0)->count())
            <section id="dp" class="panel scroll-mt-28 p-5" aria-labelledby="booking-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="booking-title" class="font-black">Pembayaran DP &amp; cicilan</h2>
                    <div class="flex flex-wrap gap-2 text-xs font-bold">
                        <span class="rounded-full bg-white/5 px-3 py-1 text-slate-300">{{ $bookings->count() }} akun</span>
                        @if ($overdue)
                            <span class="rounded-full bg-rose-400/15 px-3 py-1 text-rose-300">{{ $overdue }} lewat tempo</span>
                        @endif
                    </div>
                </div>

                <details class="mt-4 rounded-xl border border-amber-300/25 bg-amber-300/5 p-3" @if ($errors->has('tiers') || $errors->has('tiers.*') || $errors->has('dp_min_percent')) open @endif>
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 [&::-webkit-details-marker]:hidden">
                        <span class="text-xs font-bold text-amber-300">Aturan toko <span class="font-normal text-slate-300">· DP {{ \App\Models\Setting::downPaymentPercent() ?? '-' }}% · {{ collect(\App\Models\Setting::dueTiers())->pluck('days')->implode('/') ?: '-' }} hari</span></span>
                        <span class="text-xs font-bold text-lime-300">Ubah aturan ▾</span>
                    </summary>
                    <form method="POST" action="{{ route('admin.dp-terms.update') }}" class="mt-3 space-y-3">
                        @csrf @method('PUT')
                        @include('admin.dp-terms-fields')
                        <div class="flex flex-wrap items-center gap-3">
                            <button class="btn">Simpan aturan</button>
                            <a href="{{ route('admin.settings') }}#dp-terms" class="text-xs font-semibold text-lime-300 hover:underline">Ubah teks ketentuan</a>
                            <a href="{{ route('installment') }}" target="_blank" rel="noopener" class="text-xs font-semibold text-slate-400 hover:text-white">Lihat halaman Cicilan ↗</a>
                        </div>
                    </form>
                </details>

                <ul class="mt-4 space-y-3">
                    @forelse ($bookings as $booking)
                        @php($daysLeft = $booking->daysUntilPaymentDue())
                        <li class="grid gap-3 rounded-xl border border-white/10 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center {{ $daysLeft !== null && $daysLeft < 0 ? 'border-rose-400/40 bg-rose-400/5' : '' }}">
                            <div class="flex min-w-0 items-center gap-3">
                                <img src="{{ $booking->imageUrl() }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover object-top" loading="lazy">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('admin.accounts.edit', $booking) }}" class="truncate text-sm font-bold text-white hover:text-lime-300">{{ $booking->title }}</a>
                                        <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-black {{ $booking->status === 'dp' ? 'bg-amber-300/15 text-amber-300' : 'bg-violet-300/15 text-violet-300' }}">{{ $booking->statusLabel() }}</span>
                                        @if ($daysLeft !== null)
                                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-black {{ $daysLeft < 0 ? 'bg-rose-400 text-slate-950' : ($daysLeft <= 3 ? 'bg-amber-300 text-slate-950' : 'bg-white/10 text-slate-200') }}">{{ $daysLeft < 0 ? 'Lewat '.abs($daysLeft).' hari' : ($daysLeft === 0 ? 'Jatuh tempo hari ini' : $daysLeft.' hari lagi') }}</span>
                                        @endif
                                    </div>
                                    <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-white/10" role="img" aria-label="{{ $booking->dp_percent ?? 0 }}% sudah dibayar"><span class="block h-full rounded-full bg-lime-300" style="width: {{ $booking->dp_percent ?? 0 }}%"></span></span>
                                    <span class="mt-1 block text-xs text-slate-400">Harga {{ $booking->formattedPrice() }} · Dibayar {{ 'Rp '.number_format($booking->paidAmount(), 0, ',', '.') }} · Sisa <strong class="text-white">{{ 'Rp '.number_format($booking->remainingAmount(), 0, ',', '.') }}</strong></span>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.accounts.payment', $booking) }}" class="grid grid-cols-[5.5rem_minmax(0,1fr)] items-end gap-2 sm:flex sm:flex-wrap">
                                @csrf @method('PATCH')
                                <label class="text-[11px] text-slate-400 sm:w-24">% dibayar
                                    <input class="field mt-1 px-3 py-2" name="dp_percent" type="number" inputmode="numeric" min="1" max="100" value="{{ $booking->dp_percent }}" placeholder="%" aria-label="Persen dibayar {{ $booking->title }}">
                                </label>
                                <label class="min-w-0 text-[11px] text-slate-400 sm:w-44">Jatuh tempo
                                    <input class="field mt-1 px-3 py-2" name="payment_due_on" type="date" value="{{ $booking->payment_due_on?->toDateString() }}" aria-label="Jatuh tempo {{ $booking->title }}">
                                </label>
                                <button class="btn-secondary col-span-2 shrink-0 sm:col-auto">Simpan</button>
                            </form>
                        </li>
                    @empty
                        <li class="rounded-xl border border-dashed border-[#a9c3ff]/20 p-4 text-center text-sm text-slate-400">Belum ada akun berstatus Sudah DP atau Sudah dicicil. Ubah status akun di menu Akun game.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Headline numbers. --}}
            <section class="panel p-5" aria-labelledby="totals-title">
                <h2 id="totals-title" class="font-black">Angka toko</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
                    @foreach ([
                        ['Total akun', $total, 'linear-gradient(135deg, #4f63f0, #7d8cff)', 'gamepad'],
                        ['Akun tersedia', $available, 'linear-gradient(135deg, #7048e8, #9b7bff)', 'check'],
                        ['Akun terjual', $sold, 'linear-gradient(135deg, #a23fd8, #cf6cf0)', 'bag'],
                        ['Pendapatan ' . ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][now('Asia/Jakarta')->month - 1], $rupiahShort($monthRevenue), 'linear-gradient(135deg, #d9468f, #f377b5)', 'cash'],
                    ] as [$label, $value, $gradient, $icon])
                        <div class="min-w-0 rounded-2xl p-4 text-[#fff] shadow-lg shadow-[#020617]/20 md:p-5" style="background: {{ $gradient }}">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fff]/20" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    @switch($icon)
                                        @case('gamepad')
                                            <path d="M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7Z" />
                                            <path d="M7.5 10v5M5 12.5h5" />
                                        @break

                                        @case('check')
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="m8 12 2.5 2.5L16 9" />
                                        @break

                                        @case('bag')
                                            <path d="M5 7h14l1 14H4L5 7ZM9 7V6a3 3 0 0 1 6 0v1" />
                                        @break

                                        @case('cash')
                                            <rect x="2" y="6" width="20" height="12" rx="2" />
                                            <circle cx="12" cy="12" r="2.5" />
                                            <path d="M6 10v4M18 10v4" />
                                        @break
                                    @endswitch
                                </svg>
                            </span>
                            <p class="mt-4 truncate font-black {{ mb_strlen((string) $value) > 7 ? 'text-xl md:text-2xl' : 'text-2xl md:text-3xl' }}" title="{{ $value }}">{{ $value }}</p>
                            <p class="mt-1 truncate text-xs font-semibold text-[#fff]/85">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="latest-title">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 id="latest-title" class="text-xl font-bold">Akun terbaru</h2>
                    <a class="btn w-full sm:w-auto" href="{{ route('admin.accounts.create') }}">+ Tambah akun</a>
                </div>
                @include('admin.account-table')
            </section>
        </div>

        <aside class="min-w-0 space-y-5">
            {{-- Stock per category: share already sold. --}}
            <section class="panel p-5" aria-labelledby="category-title">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="category-title" class="font-black">Stok per kategori</h2>
                    <a href="{{ route('admin.categories') }}" class="rounded-full bg-lime-300/15 px-3 py-1 text-xs font-bold text-lime-300 hover:bg-lime-300/25">{{ $categories }} kategori</a>
                </div>
                <ul class="mt-4 space-y-4">
                    @forelse ($categoryStock as $category)
                        @php($soldShare = $category->accounts_count ? (int) round($category->sold_count / $category->accounts_count * 100) : 0)
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate font-bold text-white">{{ $category->name }}</span>
                                <span class="shrink-0 text-xs font-bold text-slate-300">{{ $soldShare }}%</span>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-[#a9c3ff]/10" role="img" aria-label="{{ $category->name }}: {{ $soldShare }}% terjual">
                                <div class="h-full rounded-full bg-lime-300" style="width: {{ $soldShare }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-400">{{ $category->sold_count }} dari {{ $category->accounts_count }} terjual · {{ $category->available_count }} tersedia{{ $category->booked_count ? " · {$category->booked_count} DP/cicil" : "" }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Belum ada kategori.</li>
                    @endforelse
                </ul>
            </section>

            <a class="btn w-full" href="{{ route('admin.accounts.create') }}">+ Tambah akun baru</a>

            {{-- Stock by status: donut with the sold share in the middle. --}}
            <section class="panel p-5 text-center" aria-labelledby="status-title">
                <h2 id="status-title" class="text-lg font-black">Progres penjualan</h2>
                @php($donutTotal = max(1, collect($stockSegments)->sum('value')))
                @php($donutOffset = 0)
                <div class="relative mx-auto mt-4 h-48 w-48">
                    <svg viewBox="0 0 42 42" class="h-full w-full -rotate-90" role="img" aria-label="{{ collect($stockSegments)->map(fn ($s) => $s['label'].' '.$s['value'])->implode(', ') }}">
                        <circle cx="21" cy="21" r="15.9155" fill="none" style="stroke: var(--viz-track)" stroke-width="5" />
                        @foreach ($stockSegments as $segment)
                            @if ($segment['value'])
                                @php($length = $segment['value'] / $donutTotal * 100)
                                <circle cx="21" cy="21" r="15.9155" fill="none" style="stroke: {{ $segment['color'] }}" stroke-width="5"
                                    stroke-dasharray="{{ max(0.01, $length - ($length < 100 ? 0.8 : 0)) }} {{ 100 - max(0.01, $length - ($length < 100 ? 0.8 : 0)) }}" stroke-dashoffset="{{ -$donutOffset }}">
                                    <title>{{ $segment['label'] }}: {{ $segment['value'] }} akun</title>
                                </circle>
                                @php($donutOffset += $length)
                            @endif
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-white">{{ $soldPercent }}%</span>
                        <span class="text-xs font-semibold text-slate-400">akun terjual</span>
                    </div>
                </div>
                <ul class="mt-5 grid grid-cols-2 gap-x-4 gap-y-2 text-left text-xs">
                    @foreach ($stockSegments as $segment)
                        <li class="flex items-center gap-2 text-slate-300">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $segment['color'] }}" aria-hidden="true"></span>
                            {{ $segment['label'] }}
                            <strong class="ml-auto text-white">{{ $segment['value'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
@endsection

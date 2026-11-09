@extends('layouts.app')
@section('title', 'Joki Mobile Legends')
@section('description', 'Joki rank Mobile Legends dengan harga per bintang. Pilih rank, lalu pesan langsung melalui WhatsApp admin.')
@section('content')
    @php
        $whatsappNumber = \App\Models\Setting::valueFor('whatsapp_number');
        $rupiah = fn (int $price): string => 'Rp '.number_format($price, 0, ',', '.');
        $gamepadIcon = 'M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7ZM7.5 10v5M5 12.5h5';
        $chatIcon = 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z';
        $trophyIcon = 'M8 21h8M12 17v4M7 4h10v6a5 5 0 0 1-10 0V4ZM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3';
        $ranks = [
            ['Epic', 6000, '#6ee7b7'],
            ['Legend', 7000, '#fdba74'],
            ['Mythic', 8000, '#c4b5fd'],
            ['Mythical Honor', 9000, '#f9a8d4'],
            ['Mythical Glory', 11000, '#fde047'],
            ['Mythical Immortal', 15000, '#fb7185'],
        ];
        $features = [
            ['Sesuai target kamu', 'Pesan berdasarkan rank saat ini dan jumlah bintang yang ingin dicapai.', '#c4b5fd', 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
            ['Data akun tidak disimpan', 'Formulir hanya menyusun pesan WhatsApp. Data tidak dikirim ke server website.', '#6ee7b7', 'M5 11h14v10H5V11ZM8 11V7a4 4 0 0 1 8 0v4'],
            ['Update lewat WhatsApp', 'Admin mengabari saat pesanan mulai dikerjakan dan ketika sudah selesai.', '#7dd3fc', $chatIcon],
            ['Harga per bintang jelas', 'Setiap rank punya harga per bintang, jadi total biaya mudah dihitung.', '#fcd34d', 'M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-8.1-8.1a2 2 0 0 1-.5-1.4V4a2 2 0 0 1 2-2h7.1a2 2 0 0 1 1.4.6l8.1 8.1a2 2 0 0 1 0 2.7ZM7.5 7.5h.01'],
        ];
        $steps = [
            ['Pilih rank', 'Lihat tangga rank dan tentukan jumlah bintang yang ingin dinaikkan.', 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', false],
            ['Isi data akun', 'Lengkapi formulir pesanan di halaman ini.', 'M5 11h14v10H5V11ZM8 11V7a4 4 0 0 1 8 0v4', false],
            ['Kirim lewat WhatsApp', 'Tekan tombol pesan, lalu tempel password pada pesan WhatsApp yang sudah disiapkan.', $chatIcon, false],
            ['Konfirmasi dan bayar', 'Admin mengonfirmasi total biaya dan menjelaskan cara pembayaran.', 'M2 7a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7ZM2 10h20', true],
            ['Akun dikerjakan', 'Jangan login ke game selama akun sedang dikerjakan.', $gamepadIcon, true],
            ['Terima kabar selesai', 'Admin memberi tahu melalui WhatsApp saat pengerjaan selesai.', 'M3 17l6-6 4 4 8-8M15 7h6v6', false],
        ];
    @endphp
    <div class="shell space-y-14 py-10">
        <div>
            <p class="eyebrow">Layanan joki</p>
            <h1 class="mt-3 text-4xl font-black">Joki Mobile Legends<span class="text-lime-300">.</span></h1>
            @unless ($whatsappNumber)
                <p class="panel mt-6 max-w-xl p-5 text-sm text-slate-400">Kontak WhatsApp belum diatur oleh admin.</p>
            @endunless
            @if ($whatsappNumber)
                <section id="pesan" class="mt-8 scroll-mt-24" aria-labelledby="joki-form-title">
                    <form class="joki-card overflow-hidden" data-joki-form data-whatsapp-url="https://wa.me/{{ $whatsappNumber }}" autocomplete="off">
                        <div class="flex items-center gap-4 bg-[#0b1230]">
                            <span class="flex size-12 shrink-0 items-center justify-center bg-amber-300 text-lg font-black text-slate-950" aria-hidden="true">1</span>
                            <h2 id="joki-form-title" class="font-bold">Masukkan Data Akun</h2>
                        </div>
                        <div class="grid gap-5 p-5 md:grid-cols-2 md:p-6">
                            <div>
                                <label class="label" for="joki-rank">Rank saat ini</label>
                                <select id="joki-rank" class="field" name="rank" required>
                                    @foreach ($ranks as [$name, $price])
                                        <option value="{{ $name }}" data-price="{{ $price }}">{{ $name }} ({{ $rupiah($price) }}/bintang)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="joki-stars">Jumlah bintang</label>
                                <input id="joki-stars" class="field" type="number" name="stars" min="1" max="500" required placeholder="Contoh: 10">
                            </div>
                            <div>
                                <label class="label" for="joki-login-via">Login Via</label>
                                <select id="joki-login-via" class="field" name="loginVia" required>
                                    <option value="">Pilih Login Via</option>
                                    @foreach (['Moonton', 'Facebook', 'Google Play', 'TikTok', 'VK'] as $loginOption)
                                        <option>{{ $loginOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="joki-user-id">User ID &amp; Nick Name</label>
                                <input id="joki-user-id" class="field" type="text" name="userId" maxlength="100" required placeholder="Masukkan User ID & Nick Name">
                            </div>
                            <div>
                                <label class="label" for="joki-login-id">Email/No. Hp/Moonton ID</label>
                                <input id="joki-login-id" class="field" type="text" name="loginId" maxlength="100" required placeholder="Masukkan Email/No. Hp/Moonton ID">
                            </div>
                            <div>
                                <label class="label" for="joki-password">Password</label>
                                <input id="joki-password" class="field" type="password" name="password" maxlength="100" required autocomplete="new-password" placeholder="Masukkan Password">
                            </div>
                            <div>
                                <label class="label" for="joki-heroes">Request Hero (Minimal 3 Hero)</label>
                                <input id="joki-heroes" class="field" type="text" name="heroes" maxlength="200" required placeholder="Contoh: Layla, Miya, Tigreal">
                            </div>
                            <div>
                                <label class="label" for="joki-notes">Catatan Untuk Penjoki</label>
                                <input id="joki-notes" class="field" type="text" name="notes" maxlength="200" placeholder="Masukkan catatan untuk penjoki">
                            </div>
                            <p class="rounded-xl border border-amber-300/20 bg-amber-300/10 p-3 text-xs leading-5 text-amber-200 md:col-span-2">Pastikan data akun sudah benar. Password tidak dimasukkan ke tautan WhatsApp, melainkan disalin agar bisa kamu tempel di chat.</p>
                            <div class="flex flex-wrap items-center justify-between gap-4 md:col-span-2">
                                <div>
                                    <p class="muted">Total</p>
                                    <p class="text-2xl font-black text-lime-300" data-joki-total>-</p>
                                </div>
                                <button type="button" class="btn" data-joki-submit>Pesan via WhatsApp ↗</button>
                            </div>
                            <p data-joki-status role="status" aria-live="polite" class="muted hidden md:col-span-2"></p>
                        </div>
                    </form>
                    <noscript><p class="panel mt-4 p-5">Aktifkan JavaScript untuk mengisi formulir pesanan.</p></noscript>
                </section>
            @endif
            <div class="mt-8 grid grid-cols-2 gap-3 md:gap-5 xl:grid-cols-4">
                @foreach ($features as [$title, $description, $color, $icon])
                    <article class="joki-card joki-tinted p-4 md:p-5" style="--tint: {{ $color }}">
                        <span class="joki-icon" style="color: {{ $color }}"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg></span>
                        <h2 class="mt-4 font-bold">{{ $title }}</h2>
                        <p class="muted mt-1">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        <section aria-labelledby="joki-layanan">
            <div class="flex items-center gap-4">
                <span class="joki-mark"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $gamepadIcon }}" /></svg></span>
                <div>
                    <h2 id="joki-layanan" class="text-2xl font-bold tracking-tight">Pilih Layanan Joki</h2>
                    <p class="muted">Naik rank dengan harga per bintang.</p>
                </div>
            </div>
            <article class="joki-card mt-6 max-w-2xl overflow-hidden">
                <div class="joki-banner flex h-24 items-center justify-center text-white"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $trophyIcon }}" /></svg></div>
                <div class="p-6">
                    <span class="joki-icon relative -mt-14 size-20 overflow-hidden"><img class="h-full w-full object-cover" src="{{ asset('images/joki/mobile-legends.webp') }}" alt="Logo Mobile Legends" width="160" height="160"></span>
                    <h3 class="mt-4 text-xl font-bold">Joki Mobile Legends</h3>
                    <p class="muted mt-2">Joki rank dari Epic sampai Mythical Immortal. Harga menyesuaikan rank saat ini.</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs"><span class="status text-slate-300">{{ count($ranks) }} tingkat rank</span><span class="status text-slate-300">Harga per bintang</span></div>
                    <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="muted">mulai</p>
                            <p class="text-2xl font-black text-lime-300">{{ $rupiah($ranks[0][1]) }}<span class="text-sm font-semibold text-slate-400">/bintang</span></p>
                        </div>
                        @if ($whatsappNumber)
                            <a class="btn" href="#pesan">Pesan sekarang →</a>
                        @endif
                    </div>
                </div>
            </article>
        </section>

        <section aria-labelledby="joki-cara">
            <div class="flex items-center gap-4">
                <span class="joki-mark"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 17l6-6 4 4 8-8M15 7h6v6" /></svg></span>
                <div>
                    <h2 id="joki-cara" class="text-2xl font-bold tracking-tight">Cara Kerja Joki</h2>
                    <p class="muted">Semua alurnya berjalan melalui WhatsApp admin.</p>
                </div>
            </div>
            <div class="mt-6 grid gap-3 md:grid-cols-2 md:gap-5 xl:grid-cols-3">
                @foreach ($steps as [$title, $description, $icon, $isHighlighted])
                    <article class="joki-card flex items-start gap-4 p-4 md:block md:p-5 {{ $isHighlighted ? 'joki-tinted' : '' }}" @if ($isHighlighted) style="--tint: #6ee7b7" @endif>
                        <div class="flex items-center gap-3">
                            <span class="joki-icon text-lime-300"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg></span>
                            <span class="hidden text-3xl font-black text-white/20 md:inline" aria-hidden="true">0{{ $loop->iteration }}</span>
                        </div>
                        <div class="min-w-0 md:mt-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Langkah {{ $loop->iteration }}</p>
                            <h3 class="mt-1 font-bold">{{ $title }}</h3>
                            <p class="muted mt-1">{{ $description }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="joki-harga">
            <div class="flex items-center gap-4">
                <span class="joki-mark"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $trophyIcon }}" /></svg></span>
                <div>
                    <h2 id="joki-harga" class="text-2xl font-bold tracking-tight">Tangga Rank &amp; Harga per Bintang</h2>
                    <p class="muted">{{ $whatsappNumber ? 'Klik rank untuk mengisi formulir pesanan.' : 'Harga joki Mobile Legends.' }}</p>
                </div>
            </div>
            <div class="joki-card mt-6 p-3 md:p-6">
                <div class="joki-ladder">
                    @foreach ($ranks as [$name, $price, $color])
                        @php($tag = $whatsappNumber ? 'a' : 'div')
                        <{{ $tag }} class="joki-rank group" style="--rank: {{ $color }}; --i: {{ $loop->index }}"
                            @if ($whatsappNumber) href="#pesan" data-joki-rank="{{ $name }}" aria-label="Pilih rank {{ $name }}, {{ $rupiah($price) }} per bintang" @endif>
                            <span class="flex min-w-0 flex-1 items-center gap-3 md:w-full md:flex-none md:flex-col md:items-start md:gap-2">
                                <span class="joki-icon size-10 overflow-hidden"><img class="h-full w-full object-cover" src="{{ asset('images/joki/rank-'.\Illuminate\Support\Str::slug($name).'.webp') }}" alt="" width="36" height="36" loading="lazy"></span>
                                <span class="flex min-w-0 flex-1 items-center justify-between gap-2 md:block md:flex-none">
                                    <span class="min-w-0 text-sm font-bold md:block md:text-xs">{{ $name }}</span>
                                    <span class="shrink-0 text-right md:block md:text-left">
                                        <span class="block text-sm font-black text-lime-300">{{ $rupiah($price) }}</span>
                                        <span class="block text-[10px] text-slate-400">per bintang</span>
                                    </span>
                                </span>
                                @if ($whatsappNumber)
                                    <svg class="h-5 w-5 shrink-0 text-slate-400 md:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6" /></svg>
                                @endif
                            </span>
                            <span class="hidden w-full md:block"><span class="joki-bar block transition group-hover:brightness-110"></span></span>
                        </{{ $tag }}>
                    @endforeach
                </div>
                <p class="mt-5 rounded-xl bg-white/5 p-4 text-sm text-slate-300">Harga dihitung per bintang sesuai rank saat ini. Contoh: 10 bintang di {{ $ranks[0][0] }} = <strong class="text-white">{{ $rupiah(10 * $ranks[0][1]) }}</strong>.</p>
            </div>
        </section>
    </div>
@endsection

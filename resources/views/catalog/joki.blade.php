@extends('layouts.app')
@section('title', 'Joki Mobile Legends')
@section('description', 'Joki rank Mobile Legends: hitung harga per bintang dengan kalkulator joki atau pilih paket, lalu pesan langsung melalui WhatsApp admin.')
@section('content')
    @php
        $whatsappNumber = \App\Models\Setting::valueFor('whatsapp_number');
        $tiers = config('joki.tiers');
        $services = config('joki.services');
        $packageGroups = config('joki.packages');
        $rupiah = fn (int $price): string => 'Rp '.number_format($price, 0, ',', '.');
        $tierIcon = fn (string $tier): string => asset('images/joki/rank-'.\Illuminate\Support\Str::slug($tier).'.webp');
        // Icon of a package = the tier it ends in ("Legend V – Mythic Honor" → Mythical Honor).
        $packageTier = function (string $package) use ($tiers): string {
            $target = trim(\Illuminate\Support\Str::afterLast($package, '–'));
            $lastWord = \Illuminate\Support\Str::afterLast($target, ' ');
            foreach ($tiers as $tier) {
                if (in_array($lastWord, explode(' ', $tier['name']), true) || str_starts_with($target, $tier['name'].' ')) {
                    $match = $tier['name'];
                }
            }

            return $match ?? $tiers[0]['name'];
        };
        $chatIcon = 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z';
        $gamepadIcon = 'M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7ZM7.5 10v5M5 12.5h5';
        $serviceIcons = [
            'regular' => $gamepadIcon,
            'role' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM16.5 13v7M13 16.5h7',
            'hero' => 'M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-4Z',
            'gendong' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
        ];
        $steps = [
            ['Pilih rank atau paket', 'Hitung harga di kalkulator joki, atau tambahkan paket siap pakai.'],
            ['Isi detail akun & kontak', 'Data hanya dipakai untuk menyusun pesan WhatsApp, tidak disimpan di website.'],
            ['Kirim lewat WhatsApp', 'Tempel password yang sudah disalin pada baris Password di chat.'],
            ['Konfirmasi dan bayar', 'Admin mengonfirmasi total biaya dan cara pembayaran.'],
            ['Akun dikerjakan', 'Jangan login ke game selama akun sedang dikerjakan.'],
            ['Terima kabar selesai', 'Admin memberi tahu melalui WhatsApp saat pengerjaan selesai.'],
        ];
    @endphp
    <div class="shell py-6 md:py-10">
        <nav class="text-sm text-slate-400" aria-label="Breadcrumb">
            <a class="hover:text-white" href="{{ route('catalog') }}">Beranda</a> <span aria-hidden="true">›</span> <span>Joki</span> <span aria-hidden="true">›</span> <span class="text-white">Mobile Legends</span>
        </nav>
        <h1 class="mt-3 text-3xl font-black md:text-4xl">Joki Mobile Legends<span class="text-lime-300">.</span></h1>

        @unless ($whatsappNumber)
            <p class="panel mt-6 max-w-xl p-5 text-sm text-slate-400">Kontak WhatsApp belum diatur oleh admin.</p>
            <section class="panel mt-6 p-5" aria-labelledby="joki-price-list">
                <h2 id="joki-price-list" class="font-black">Harga per bintang</h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tiers as $tier)
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-white/5 px-3 py-2 text-sm"><span>{{ $tier['name'] }}</span><strong class="text-lime-300">{{ $rupiah($tier['price']) }}</strong></li>
                    @endforeach
                </ul>
                <h2 class="mt-6 font-black">Paket Joki</h2>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    @foreach ($packageGroups as $groupName => $packages)
                        <div>
                            <h3 class="text-sm font-bold text-slate-300">{{ $groupName }}</h3>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($packages as [$packageName, $packagePrice])
                                    <li class="flex items-center justify-between gap-3 rounded-xl bg-white/5 px-3 py-2 text-sm"><span>{{ $packageName }}</span><strong class="text-lime-300">{{ $rupiah($packagePrice) }}</strong></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>
        @endunless

        @if ($whatsappNumber)
            <form class="mt-6" data-joki-form data-whatsapp-url="https://wa.me/{{ $whatsappNumber }}" autocomplete="off" novalidate
                data-joki-tiers="{{ json_encode(collect($tiers)->map(fn ($tier) => [...$tier, 'icon' => $tierIcon($tier['name'])])->values()) }}"
                data-joki-services="{{ json_encode($services) }}">
                {{-- Game bar with the two ways to order. --}}
                <div class="panel flex flex-wrap items-center justify-between gap-3 px-4 py-2 md:px-5">
                    <span class="flex items-center gap-3 font-black"><img src="{{ asset('images/joki/mobile-legends.webp') }}" alt="" width="36" height="36" class="size-9 rounded-lg object-cover">Mobile Legends</span>
                    <div role="tablist" aria-label="Cara memesan joki" class="flex gap-1">
                        @foreach (['calculator' => 'Kalkulator Joki', 'package' => 'Paket Joki'] as $tab => $tabLabel)
                            <button type="button" role="tab" id="joki-tab-{{ $tab }}" aria-controls="joki-panel-{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-joki-tab="{{ $tab }}"
                                class="relative min-h-11 rounded-lg px-3 text-sm font-bold text-slate-400 transition hover:text-white aria-selected:text-white after:absolute after:inset-x-3 after:-bottom-0.5 after:h-0.5 after:rounded-full after:bg-lime-300 after:opacity-0 aria-selected:after:opacity-100 md:px-4">{{ $tabLabel }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                    <div class="min-w-0 space-y-6">
                        {{-- Calculator: rank + stars, service type. --}}
                        <fieldset id="joki-panel-calculator" role="tabpanel" aria-labelledby="joki-tab-calculator" data-joki-section="calculator" data-joki-panel="calculator" class="panel min-w-0 p-5 md:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-black">Pemilihan rank dan bintang</h2>
                                    <p class="muted mt-1">Pilih rank saat ini dan rank tujuan, lalu atur jumlah bintang yang tepat.</p>
                                </div>
                                <details class="relative shrink-0">
                                    <summary class="theme-toggle list-none [&::-webkit-details-marker]:hidden" aria-label="Harga per bintang">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9" /><path d="M12 11v5M12 8h.01" /></svg>
                                    </summary>
                                    <div class="absolute right-0 top-full z-30 mt-2 w-72 rounded-2xl border border-white/10 bg-[#101833] p-4 shadow-2xl shadow-[#020617]/60">
                                        <p class="text-sm font-black">Harga per bintang</p>
                                        <ul class="mt-2 space-y-1.5 text-sm">
                                            @foreach ($tiers as $tier)
                                                <li class="flex items-center justify-between gap-3"><span class="flex items-center gap-2"><img src="{{ $tierIcon($tier['name']) }}" alt="" width="20" height="20" class="size-5 rounded" loading="lazy">{{ $tier['name'] }}</span><strong class="text-lime-300">{{ $rupiah($tier['price']) }}</strong></li>
                                            @endforeach
                                        </ul>
                                        <p class="muted mt-3 text-xs">Epic &amp; Legend: 5 bintang per divisi. Mythic 0–24, Honor 25–49, Glory 50–99, Immortal 100+ bintang.</p>
                                    </div>
                                </details>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                                @foreach ([['fromRank', 'fromStars', 'Rank saat ini', 'Bintang saat ini'], ['toRank', 'toStars', 'Rank tujuan', 'Bintang tujuan']] as [$rankName, $starName, $rankLabel, $starLabel])
                                    <div>
                                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400" for="joki-{{ $rankName }}">{{ $rankLabel }}</label>
                                        <div class="relative">
                                            <img data-joki-rank-icon="{{ $rankName }}" src="{{ $tierIcon($tiers[0]['name']) }}" alt="" width="24" height="24" class="pointer-events-none absolute left-3 top-1/2 size-6 -translate-y-1/2 rounded">
                                            <select id="joki-{{ $rankName }}" name="{{ $rankName }}" class="field min-h-12 pl-11 font-semibold"></select>
                                        </div>
                                        <label class="mb-2 mt-4 block text-xs font-bold uppercase tracking-wider text-slate-400" for="joki-{{ $starName }}">{{ $starLabel }}</label>
                                        <div class="flex h-12 items-stretch overflow-hidden rounded-xl border border-white/10 bg-[#080f25]">
                                            <button type="button" data-step="{{ $starName }}:-1" class="flex w-12 items-center justify-center text-xl text-slate-300 transition hover:bg-white/5 hover:text-white disabled:opacity-30" aria-label="Kurangi {{ strtolower($starLabel) }}">−</button>
                                            <input id="joki-{{ $starName }}" name="{{ $starName }}" type="number" inputmode="numeric" value="0" min="0" class="min-w-0 flex-1 border-x border-white/10 bg-transparent text-center font-black text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                            <button type="button" data-step="{{ $starName }}:1" class="flex w-12 items-center justify-center text-xl text-slate-300 transition hover:bg-white/5 hover:text-white disabled:opacity-30" aria-label="Tambah {{ strtolower($starLabel) }}">+</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <h3 class="mt-7 font-black">Jenis layanan</h3>
                            <div class="mt-3 grid grid-cols-2 gap-2 sm:gap-3">
                                @foreach ($services as $key => $service)
                                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-white/10 p-3 transition hover:border-white/25 has-[:checked]:border-lime-300 has-[:checked]:bg-lime-300/5 sm:gap-3 sm:p-4">
                                        <input type="radio" name="service" value="{{ $key }}" class="mt-0.5 shrink-0 accent-lime-300 sm:mt-1" @checked($loop->first)>
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-1.5 text-sm font-bold leading-5 text-white sm:gap-2 sm:text-base"><svg class="hidden h-5 w-5 shrink-0 text-lime-300 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $serviceIcons[$key] ?? $gamepadIcon }}" /></svg>{{ $service['label'] }}</span>
                                            <span class="mt-1 block text-[11px] leading-4 text-slate-400 sm:text-xs sm:leading-5">{{ $service['description'] }}@if ($service['extra']) <strong class="text-amber-300">+{{ $service['extra'] }}%</strong>@endif</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            <fieldset data-joki-section="role" hidden disabled class="mt-5 rounded-xl border border-white/10 p-4">
                                <label class="label" for="joki-role">Role yang diutamakan</label>
                                <select id="joki-role" name="role" class="field" required>
                                    <option value="">Pilih role</option>
                                    @foreach (config('joki.roles') as $role)
                                        <option>{{ $role }}</option>
                                    @endforeach
                                </select>
                            </fieldset>
                        </fieldset>

                        {{-- Packages: fixed price from a start rank to a target rank. --}}
                        <fieldset id="joki-panel-package" role="tabpanel" aria-labelledby="joki-tab-package" data-joki-section="package" data-joki-panel="package" hidden disabled class="min-w-0 space-y-6">
                            @foreach ($packageGroups as $groupName => $packages)
                                <section class="panel overflow-hidden" aria-label="{{ $groupName }}">
                                    <div class="border-b border-white/10 px-5 py-4">
                                        <h2 class="font-black uppercase">{{ $groupName }}</h2>
                                        <p class="mt-0.5 text-xs uppercase tracking-wide text-slate-400">{{ count($packages) }} paket · sekali bayar sampai rank tujuan</p>
                                    </div>
                                    <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($packages as $package)
                                            @php([$packageName, $packagePrice] = $package)
                                            @php($originalPrice = $package[2] ?? null)
                                            <article class="relative flex flex-col rounded-xl border border-white/10 bg-[#0b1230] p-4">
                                                @if ($originalPrice && $originalPrice > $packagePrice)
                                                    <span class="absolute -left-1.5 -top-2 rounded-md bg-rose-400 px-1.5 py-0.5 text-[10px] font-black text-slate-950">{{ round((1 - $packagePrice / $originalPrice) * 100) }}%</span>
                                                @endif
                                                <div class="flex items-start gap-3">
                                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5"><img src="{{ $tierIcon($packageTier($packageName)) }}" alt="" width="32" height="32" class="size-8 rounded" loading="lazy"></span>
                                                    <h3 class="line-clamp-2 text-sm font-black leading-5">{{ $packageName }}</h3>
                                                </div>
                                                <div class="mt-4">
                                                    @if ($originalPrice && $originalPrice > $packagePrice)
                                                        <p class="text-[11px] text-slate-500 line-through">{{ $rupiah($originalPrice) }}</p>
                                                    @endif
                                                    <p class="font-black text-lime-300">{{ $rupiah($packagePrice) }}</p>
                                                </div>
                                                <button type="button" data-joki-add-package="{{ $packageName }}" data-price="{{ $packagePrice }}" aria-pressed="false"
                                                    class="mt-3 min-h-10 rounded-xl border border-white/10 text-sm font-bold text-slate-200 transition hover:border-lime-300/50 hover:text-white aria-pressed:border-lime-300 aria-pressed:bg-lime-300/10 aria-pressed:text-lime-300">Tambah</button>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </fieldset>

                        <fieldset data-joki-section="heroes" class="panel min-w-0 p-5 md:p-6">
                            <h2 class="font-black">Hero yang diminta</h2>
                            <p class="muted mt-1">Tambahkan minimal {{ 3 }} hero yang ingin diutamakan selama joki.</p>
                            <div data-joki-heroes class="mt-4 grid gap-3 sm:grid-cols-3">
                                @foreach (range(1, 3) as $heroNumber)
                                    <input class="field" type="text" name="heroes[]" maxlength="40" placeholder="Hero {{ $heroNumber }}" aria-label="Hero {{ $heroNumber }}">
                                @endforeach
                            </div>
                            <button type="button" data-joki-add-hero class="mt-3 inline-flex min-h-10 items-center gap-1.5 text-sm font-bold text-lime-300 hover:underline">+ Tambah hero lain</button>
                        </fieldset>

                        <section id="joki-detail" class="panel scroll-mt-24 p-5 md:p-6" aria-labelledby="joki-detail-title">
                            <h2 id="joki-detail-title" class="text-lg font-black">Detail joki</h2>
                            <p class="muted mt-1">Berikan data akun game untuk proses joki.</p>
                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div><label class="label" for="joki-user-id">ID Pengguna</label><input id="joki-user-id" class="field" type="text" name="userId" inputmode="numeric" maxlength="30" required placeholder="contoh: 123456789"></div>
                                <div><label class="label" for="joki-server-id">ID Server</label><input id="joki-server-id" class="field" type="text" name="serverId" inputmode="numeric" maxlength="10" placeholder="contoh: 2001"></div>
                            </div>
                            <fieldset name="login" data-joki-section="login" class="mt-4 grid min-w-0 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="label" for="joki-login-via">Masuk melalui</label>
                                    <select id="joki-login-via" class="field" name="loginVia" required>
                                        <option value="">Pilih metode masuk</option>
                                        @foreach (config('joki.login_methods') as $loginOption)
                                            <option>{{ $loginOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div><label class="label" for="joki-login-id">Email / No. HP / Moonton ID</label><input id="joki-login-id" class="field" type="text" name="loginId" maxlength="100" required placeholder="Masukkan email"></div>
                                <div>
                                    <label class="label" for="joki-password">Kata sandi</label>
                                    @include('auth.partials.password-field', ['id' => 'joki-password', 'name' => 'password', 'placeholder' => 'Masukkan kata sandi', 'autocomplete' => 'new-password'])
                                </div>
                            </fieldset>
                            <div class="mt-4"><label class="label" for="joki-notes">Catatan untuk penjoki <span class="font-normal text-slate-500">(opsional)</span></label><input id="joki-notes" class="field" type="text" name="notes" maxlength="200" placeholder="Contoh: jangan pakai hero tertentu"></div>
                        </section>

                        <section class="panel p-5 md:p-6" aria-labelledby="joki-contact-title">
                            <h2 id="joki-contact-title" class="text-lg font-black">Detail kontak</h2>
                            <p class="muted mt-1">Dipakai admin untuk mengabari pesanan dan pembayaran.</p>
                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="label" for="joki-phone">WhatsApp / Nomor kontak</label>
                                    <div class="flex overflow-hidden rounded-xl border border-[#a9c3ff]/20 bg-[#080f25] focus-within:border-lime-300/70">
                                        <span class="flex shrink-0 items-center border-r border-white/10 px-3 text-sm font-bold text-slate-300">+62</span>
                                        <input id="joki-phone" class="min-w-0 flex-1 bg-transparent px-3 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none" type="tel" name="contactPhone" inputmode="tel" maxlength="15" required pattern="[0-9 \-]{8,15}" placeholder="812 3456 7890">
                                    </div>
                                </div>
                                <div><label class="label" for="joki-name">Nama lengkap</label><input id="joki-name" class="field" type="text" name="contactName" maxlength="60" required placeholder="Namamu"></div>
                                <div><label class="label" for="joki-email">Email <span class="font-normal text-slate-500">(opsional)</span></label><input id="joki-email" class="field" type="email" name="contactEmail" maxlength="100" placeholder="kamu@contoh.com"></div>
                            </div>
                            <p class="mt-5 rounded-xl border border-amber-300/20 bg-amber-300/10 p-3 text-xs leading-5 text-amber-200">Password tidak dimasukkan ke tautan WhatsApp, melainkan disalin agar bisa kamu tempel di chat.</p>
                            {{-- On phones the estimate panel sits below this form, so the total is repeated here. --}}
                            <p class="mt-5 flex items-center justify-between gap-3 rounded-xl bg-white/5 px-4 py-3 lg:hidden"><span class="text-sm text-slate-400">Total</span><strong class="text-lg font-black text-lime-300" data-joki-total>-</strong></p>
                            <button type="button" class="btn mt-5 w-full" data-joki-submit>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $chatIcon }}" /></svg>
                                Pesan via WhatsApp
                            </button>
                            <p data-joki-status role="status" aria-live="polite" class="muted mt-3 hidden"></p>
                        </section>
                    </div>

                    <aside class="panel p-5 lg:sticky lg:top-28" aria-labelledby="joki-estimate-title">
                        <p id="joki-estimate-title" class="border-b border-white/10 pb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Estimasi pesananmu</p>
                        <div data-joki-estimate aria-live="polite" class="mt-4 space-y-2">
                            <p class="font-bold text-white">Estimasi akan muncul di sini.</p>
                            <p class="muted">Pilih rank saat ini, rank tujuan, dan jenis layanan.</p>
                        </div>
                        <button type="button" class="btn mt-5 w-full" data-joki-continue>Lanjutkan ke pesanan</button>
                    </aside>
                </div>
                <noscript><p class="panel mt-4 p-5">Aktifkan JavaScript untuk menghitung harga dan mengirim pesanan.</p></noscript>
            </form>
        @endif

        <section class="mt-14" aria-labelledby="joki-cara">
            <h2 id="joki-cara" class="text-2xl font-black tracking-tight">Cara kerja joki</h2>
            <ol class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($steps as [$title, $description])
                    <li class="panel flex items-start gap-4 p-4 md:p-5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lime-300/15 font-black text-lime-300" aria-hidden="true">{{ $loop->iteration }}</span>
                        <span><span class="block font-bold">{{ $title }}</span><span class="muted mt-1 block">{{ $description }}</span></span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
@endsection

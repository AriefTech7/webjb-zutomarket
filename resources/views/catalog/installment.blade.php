@extends('layouts.app')
@section('title', 'Cicilan & DP')
@section('description', 'Simulasi cicilan akun game: hitung DP, sisa pembayaran, dan batas waktu pelunasan sesuai harga akun.')
@section('content')
    @php
        $percent = \App\Models\Setting::downPaymentPercent();
        $tiers = \App\Models\Setting::dueTiers();
        $rules = \App\Models\Setting::ruleLines('dp_rules');
        $cancelRules = \App\Models\Setting::ruleLines('dp_cancel_rules');
        $rupiah = fn (int $amount): string => 'Rp '.number_format($amount, 0, ',', '.');
        // ?harga= comes from the "Simulasi & ketentuan" link on an account page.
        $price = min(999999999999, (int) preg_replace('/\D/', '', (string) request('harga', '')));
        $days = $price ? \App\Models\Setting::dueDaysFor($price) : null;
        $dp = $price ? \App\Models\Setting::downPaymentAmount($price) : 0;
        $activeTier = $price ? collect($tiers)->search(fn ($tier) => $tier['max'] === null || $price <= $tier['max']) : false;
        $whatsappNumber = \App\Models\Setting::valueFor('whatsapp_number');
    @endphp
    <div class="shell py-8 md:py-12">
        <p class="eyebrow">Bisa cicil</p>
        <h1 class="mt-3 text-3xl font-black md:text-4xl">Cicilan &amp; DP<span class="text-lime-300">.</span></h1>

        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
            <section class="panel p-5 md:p-6" aria-labelledby="sim-title" data-installment-sim data-percent="{{ $percent }}"
                data-tiers="{{ json_encode(array_map(fn ($tier) => ['max' => $tier['max'], 'days' => $tier['days']], $tiers)) }}">
                <h2 id="sim-title" class="text-2xl font-black">Simulasi cicilan</h2>
                <p class="muted mt-3">Masukkan harga akun untuk melihat nominal DP, sisa pembayaran, dan batas waktu pelunasan.</p>
                <div class="mt-5 space-y-3 rounded-2xl border border-white/10 bg-[#080f25] p-4 md:p-5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400" for="sim-price">Harga akun (Rp)</label>
                    <input id="sim-price" name="harga" class="field min-h-14 text-lg font-bold" type="text" inputmode="numeric" autocomplete="off" maxlength="17" data-money value="{{ $price ?: '' }}" placeholder="Contoh: 450.000">
                    <div class="rounded-xl border border-white/10 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-400">Total harga</p>
                        <p class="mt-1 text-2xl font-black text-white" data-sim="total">{{ $price ? $rupiah($price) : '-' }}</p>
                    </div>
                    @if ($percent)
                        <div class="rounded-xl border border-lime-300/50 bg-lime-300/10 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-400">DP {{ $percent }}%</p>
                            <p class="mt-1 text-2xl font-black text-lime-300" data-sim="dp">{{ $price ? $rupiah($dp) : '-' }}</p>
                        </div>
                    @endif
                    <div class="rounded-xl border border-white/10 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-400">Batas lunas</p>
                        <p class="mt-1 text-2xl font-black uppercase text-white" data-sim="days">{{ $days ? $days.' hari' : '-' }}</p>
                    </div>
                    <p class="muted" data-sim="note" aria-live="polite">{{ $price ? 'Sisa pembayaran '.$rupiah($price - $dp).' wajib dilunasi'.($days ? ' maksimal '.$days.' hari' : '').' setelah DP masuk.' : 'Masukkan harga akun untuk melihat simulasinya.' }}</p>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a class="btn" href="{{ route('catalog.all', ['available' => 1]) }}#katalog">Pilih akun ↗</a>
                    @if ($whatsappNumber)
                        <a class="btn-secondary" href="https://wa.me/{{ $whatsappNumber }}?text={{ rawurlencode('Halo admin, saya ingin tanya cicilan akun.') }}" target="_blank" rel="noopener noreferrer">Tanya admin</a>
                    @endif
                </div>
            </section>

            <div class="space-y-6">
                @if ($tiers)
                    <section class="panel p-5 md:p-6" aria-labelledby="tier-title">
                        <h2 id="tier-title" class="text-2xl font-black">Aturan cicilan</h2>
                        <ul class="mt-4 divide-y divide-white/10">
                            @foreach ($tiers as $tier)
                                <li data-tier-index="{{ $loop->index }}" @if ($activeTier === $loop->index) data-active @endif
                                    class="-mx-2 flex items-center justify-between gap-4 rounded-lg px-2 py-3.5 transition data-[active]:bg-lime-300/10">
                                    <span class="text-sm text-slate-300">{{ $tier['max'] === null ? $rupiah($tier['min']).' ke atas' : $rupiah($tier['min']).' – '.$rupiah($tier['max']) }}</span>
                                    <span class="shrink-0 font-mono text-base font-bold text-lime-300">{{ $tier['days'] }} hari</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @foreach ([['Ketentuan DP', $rules, 'M9 15l6-6M9.5 9.5h.01M14.5 14.5h.01M12 2l2.4 2.1 3.2-.3.6 3.1 2.8 1.6-1.3 2.9 1.3 2.9-2.8 1.6-.6 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.6-3.1-2.8-1.6 1.3-2.9-1.3-2.9 2.8-1.6.6-3.1 3.2.3L12 2Z'], ['Pembatalan & Penarikan', $cancelRules, 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM12 8v4M12 16h.01']] as [$title, $lines, $icon])
                    @if ($lines)
                        <section class="panel p-5 md:p-6" aria-label="{{ $title }}">
                            <svg class="h-8 w-8 text-lime-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $icon }}" /></svg>
                            <h2 class="mt-4 text-2xl font-black">{{ $title }}</h2>
                            <ul class="mt-4 divide-y divide-white/10">
                                @foreach ($lines as $line)
                                    <li class="flex gap-3 py-3 leading-7 text-slate-300"><span class="mt-2.5 size-2 shrink-0 rounded-sm bg-lime-300" aria-hidden="true"></span><span>{!! $line !!}</span></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endsection

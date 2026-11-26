{{--
    "100% aman" badge + a moving strip of the active Tripay payment logos (home page, under "Produk Terjual").
    Logos come from Tripay's channel list; the result (even a failure) is cached so the home page never waits on Tripay.
--}}
@php
    $payChannels = \App\Services\Tripay::active()
        ? \Illuminate\Support\Facades\Cache::remember('tripay.strip.'.(\App\Services\Tripay::sandbox() ? 'sandbox' : 'production'), now()->addMinutes(30), function (): array {
            try {
                return collect((new \App\Services\Tripay)->channels())->filter(fn (array $channel): bool => $channel['icon'] !== '')
                    ->map(fn (array $channel): array => ['name' => $channel['name'], 'icon' => $channel['icon']])->values()->all();
            } catch (\Throwable) {
                return [];
            }
        })
        : [];
    // Repeat short lists so one copy is wider than the strip; the track is then doubled for a seamless loop.
    $payLogos = $payChannels ? array_merge(...array_fill(0, (int) max(1, ceil(8 / count($payChannels))), $payChannels)) : [];
@endphp
@if ($payLogos)
    <section class="mt-12" aria-label="Metode pembayaran">
        <div class="panel flex flex-col gap-3 p-3 sm:flex-row sm:items-center md:gap-4 md:p-4">
            <div class="flex shrink-0 items-center justify-center gap-2.5 rounded-2xl border border-lime-300/20 bg-lime-300/5 px-3 py-2.5 md:px-4">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-lime-300/15 text-lime-300 md:size-9">
                    <svg class="size-4 md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM9 12l2 2 4-4" /></svg>
                </span>
                <span class="leading-tight"><span class="block text-xs font-black uppercase tracking-wide md:text-sm">100% Aman</span><span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 md:text-xs">Transaksi terjamin</span></span>
            </div>
            <div class="pay-strip min-w-0 w-full sm:flex-1" style="--marquee-duration: {{ max(18, count($payLogos) * 3) }}s">
                <div class="marquee-track">
                    @foreach ([false, true] as $isCopy)
                        <ul class="flex shrink-0 items-center gap-3 pr-3" @if ($isCopy) aria-hidden="true" @endif>
                            @foreach ($payLogos as $logo)
                                <li class="flex h-11 w-24 shrink-0 items-center justify-center rounded-xl bg-white px-2.5 md:h-12 md:w-28">
                                    <img src="{{ $logo['icon'] }}" alt="{{ $isCopy ? '' : $logo['name'] }}" class="max-h-7 max-w-full object-contain md:max-h-8" loading="lazy">
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif

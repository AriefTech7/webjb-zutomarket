@props(['title', 'back' => null])
{{-- Bordered frame for the signed-in account pages: pill title on top, content below. --}}
<section class="shell py-6 md:py-10">
    <div class="mx-auto max-w-xl rounded-3xl border border-[#a9c3ff]/25 bg-[#0b1230]/70 p-4 shadow-2xl shadow-[#020617]/40 sm:p-6">
        <div class="relative rounded-full border border-lime-300/30 bg-linear-to-r from-lime-300/15 via-[#101833] to-lime-300/5 py-2.5 text-center">
            @if ($back)
                <a href="{{ $back }}" class="absolute left-2 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-full text-slate-300 transition hover:bg-white/10 hover:text-white" aria-label="Kembali ke profil">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 18-6-6 6-6" /></svg>
                </a>
            @endif
            <h1 class="text-xs font-black uppercase tracking-[0.3em] text-lime-300">{{ $title }}</h1>
        </div>
        {{ $slot }}
    </div>
</section>

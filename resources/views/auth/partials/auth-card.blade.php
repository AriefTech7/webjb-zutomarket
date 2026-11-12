{{-- Compact sign-in / sign-up card: centred title, Google first, "atau", fields with placeholders. --}}
<div class="relative mx-auto max-w-md pt-10">
    <a href="{{ route('catalog') }}" class="absolute right-0 top-0 flex size-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-white/10 hover:text-white" aria-label="Tutup dan kembali ke beranda">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18M6 6l12 12" /></svg>
    </a>
    <div class="panel p-6 sm:p-8">
        <h1 class="text-center text-3xl font-black">{{ $title }}</h1>
        @if (\App\Http\Controllers\GoogleAuthController::enabled())
            <a href="{{ route('google.redirect') }}" class="mt-7 flex min-h-12 w-full items-center justify-center gap-2.5 rounded-xl border border-lime-300/40 font-bold text-lime-300 transition hover:bg-lime-300/10">
                <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5Z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7Z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2A12 12 0 0 1 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44Z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3a12 12 0 0 1-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5Z"/></svg>
                {{ $googleLabel }}
            </a>
            <p class="my-7 flex items-center gap-3 text-xs text-slate-400"><span class="h-px flex-1 bg-white/10"></span>atau<span class="h-px flex-1 bg-white/10"></span></p>
        @else
            <div class="mt-7"></div>
        @endif
        {{ $slot }}
    </div>
</div>

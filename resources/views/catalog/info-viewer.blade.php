{{-- "Info Terbaru" poster viewer: big poster, thumbnails of the other posters, then the caption (links clickable). Opened by [data-info-open] in info-viewer.js. --}}
<dialog class="info-viewer" data-info-viewer aria-label="Info Terbaru">
    <div class="info-viewer-box relative rounded-3xl border border-white/10 bg-[#101833] p-3 shadow-2xl shadow-[#020617]/60 sm:p-4">
        <button type="button" data-info-close class="absolute right-3 top-3 z-10 flex size-11 items-center justify-center rounded-full border border-white/15 bg-[#070b1c]/70 text-white backdrop-blur transition hover:bg-rose-400 hover:text-slate-950" aria-label="Tutup">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" /></svg>
        </button>
        @foreach ($infos as $info)
            <div data-info-slide="{{ $loop->index }}" @unless ($loop->first) hidden @endunless>
                <img class="mx-auto max-h-[60vh] w-auto rounded-2xl object-contain" src="{{ $info->imageUrl() }}" alt="{{ $info->title }}" loading="lazy">
            </div>
        @endforeach
        @if ($infos->count() > 1)
            <div class="mt-3 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]" aria-label="Pilih info">
                @foreach ($infos as $info)
                    <button type="button" data-info-goto="{{ $loop->index }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="Lihat {{ $info->title }}"
                        class="h-16 w-12 shrink-0 overflow-hidden rounded-lg border-2 border-transparent opacity-60 transition hover:opacity-100 aria-[current=true]:border-lime-300 aria-[current=true]:opacity-100">
                        <img class="h-full w-full object-cover" src="{{ $info->imageUrl() }}" alt="" loading="lazy">
                    </button>
                @endforeach
            </div>
        @endif
        @foreach ($infos as $info)
            <div data-info-slide="{{ $loop->index }}" class="mt-3 px-1 text-sm leading-6 text-slate-200" @unless ($loop->first) hidden @endunless>
                <p class="break-words">{{ $info->captionHtml() }}</p>
                @if ($info->link_url)
                    <a class="btn mt-3" href="{{ $info->link_url }}" target="_blank" rel="noopener noreferrer">Buka tautan ↗</a>
                @endif
            </div>
        @endforeach
    </div>
</dialog>

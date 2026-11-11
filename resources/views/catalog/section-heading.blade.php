<div class="mb-4 flex items-end justify-between gap-3">
    <div class="min-w-0">
        <h2 id="{{ $id }}" class="text-xl font-black tracking-tight md:text-2xl {{ $titleClass ?? '' }}">{{ $title }}</h2>
        @if (! empty($subtitle))
            <p class="muted text-xs">{!! $subtitle !!}</p>
        @endif
    </div>
    @if (! empty($url))
        <a class="shrink-0 text-sm font-bold {{ $linkClass ?? 'text-lime-300 hover:text-lime-200' }}" href="{{ $url }}">Lihat semua ›</a>
    @endif
</div>

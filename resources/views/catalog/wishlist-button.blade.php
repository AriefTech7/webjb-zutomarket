@php($variant = ($compact ?? false) ? 'compact' : ($variant ?? 'full'))
<button type="button" data-wishlist-toggle="{{ $account->id }}" data-account-title="{{ $account->title }}" aria-pressed="false" aria-label="Simpan {{ $account->title }} ke wishlist" disabled class="{{ ['compact' => 'absolute right-2 top-2 z-20 flex size-9 items-center justify-center rounded-xl border border-white/20 bg-[#080f25]/90 md:right-3 md:top-3 md:size-11', 'card' => 'card-action', 'detail' => 'detail-action', 'full' => 'btn-secondary inline-flex items-center justify-center gap-2'][$variant] }} text-slate-200 transition hover:text-rose-300 aria-pressed:text-rose-300 disabled:cursor-wait">
    <svg data-wishlist-heart class="{{ in_array($variant, ['card', 'detail'], true) ? 'size-4' : 'size-5' }} shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
    @if (in_array($variant, ['card', 'detail'], true))
        <span>Suka</span>
    @elseif ($variant === 'full')
        <span data-wishlist-label>Simpan ke wishlist</span>
    @endif
</button>

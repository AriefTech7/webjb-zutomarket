{{-- Badge next to the category: the admin's short card note (notes about instalments are not shown; see GameAccount::publicCardNote). --}}
@if ($note = $account->publicCardNote())
    <span class="inline-block max-w-full truncate rounded-md border border-white/15 bg-white/5 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-200">{{ $note }}</span>
@endif

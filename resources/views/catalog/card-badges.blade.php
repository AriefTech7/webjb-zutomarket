{{-- Badges next to the category: "Bisa cicil" (when the account offers instalments) and the admin's short card note. --}}
@if ($account->offersInstallment())
    <span class="inline-flex shrink-0 items-center rounded-md border border-lime-300/40 bg-lime-300/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-lime-300">Bisa cicil</span>
@endif
@if (filled($account->card_note))
    <span class="inline-block max-w-full truncate rounded-md border border-white/15 bg-white/5 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-200">{{ $account->card_note }}</span>
@endif

{{-- Per-account payment switch in the account list (super admin, the super admin's own sale accounts only). --}}
@if (auth()->user()->is_super_admin && ! $account->is_rental && $account->owner?->is_super_admin)
    <form method="POST" action="{{ route('admin.accounts.gateway', $account) }}" class="inline">
        @csrf @method('PATCH')
        <button @class(['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold transition', 'border-emerald-300/40 bg-emerald-300/10 text-emerald-300 hover:bg-emerald-300/20' => $account->use_gateway, 'border-white/15 bg-white/5 text-slate-300 hover:text-white' => ! $account->use_gateway])
            title="Klik untuk mengganti cara bayar akun ini" aria-label="{{ $account->use_gateway ? 'Akun ini dibayar di website. Klik untuk ganti ke WhatsApp' : 'Akun ini dibeli lewat WhatsApp. Klik untuk ganti ke bayar di website' }}">
            <span @class(['size-1.5 rounded-full', 'bg-emerald-300' => $account->use_gateway, 'bg-slate-400' => ! $account->use_gateway])></span>
            {{ $account->use_gateway ? 'Bayar di website' : 'Lewat WhatsApp' }}
        </button>
    </form>
@endif

{{-- DP % and payoff days per price tier; posted to admin.dp-terms.update from the dashboard and the settings page. --}}
@php($tierRows = array_pad(array_map(fn ($tier) => ['max' => $tier['max'], 'days' => $tier['days']], \App\Models\Setting::dueTiers()), 5, ['max' => null, 'days' => null]))
<div class="grid gap-4 lg:grid-cols-[10rem_minmax(0,1fr)]">
    <label class="text-xs text-slate-400">DP wajib (%)
        <input class="field mt-1 py-2" name="dp_min_percent" type="number" inputmode="numeric" min="1" max="100" value="{{ old('dp_min_percent', \App\Models\Setting::downPaymentPercent()) }}" placeholder="Contoh: 30">
    </label>
    <fieldset class="min-w-0">
        <legend class="text-xs text-slate-400">Batas lunas per harga akun</legend>
        <div class="mt-1 space-y-2">
            @foreach (old('tiers', $tierRows) as $index => $tier)
                <div class="grid grid-cols-[auto_minmax(0,1fr)_5.5rem_auto] items-center gap-2 text-xs text-slate-400">
                    <span>s.d.</span>
                    <input class="field py-2" name="tiers[{{ $index }}][max]" type="text" inputmode="numeric" autocomplete="off" maxlength="17" data-money value="{{ $tier['max'] }}" placeholder="Kosong = ke atas" aria-label="Batas harga baris {{ $index + 1 }}">
                    <input class="field py-2" name="tiers[{{ $index }}][days]" type="number" inputmode="numeric" min="1" max="365" value="{{ $tier['days'] }}" placeholder="Hari" aria-label="Hari pelunasan baris {{ $index + 1 }}">
                    <span>hari</span>
                </div>
            @endforeach
        </div>
        <p class="muted mt-2 text-xs">Kosongkan batas harga pada satu baris untuk "ke atas". Kosongkan hari untuk menghapus baris.</p>
    </fieldset>
</div>

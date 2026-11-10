<div class="panel p-6">
    <p class="muted">Paket sewa</p>
    @if ($account->status === 'rented')
        <p class="mt-3 rounded-lg bg-amber-300/15 px-3 py-2 text-sm font-bold text-amber-300">Sedang disewa
            @if ($account->ready_estimate_at)
                · estimasi ready {{ $account->ready_estimate_at->timezone(\App\Models\GameAccount::LOCAL_TIMEZONE)->format('d M Y H:i') }} WIB
            @endif
        </p>
    @endif
    <ul class="mt-3 space-y-2">
        @foreach ($account->rentalPackages as $package)
            <li class="flex items-center justify-between gap-3 rounded-xl border border-[#a9c3ff]/15 bg-[#0b1230] p-3">
                <div class="min-w-0">
                    <p class="break-words font-bold">{{ $package->duration }}</p>
                    <p class="font-black text-lime-300">{{ $package->formattedPrice() }}</p>
                </div>
                @if ($url = $account->rentalWhatsappUrl($package))
                    <a class="btn shrink-0 px-4" href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="Sewa {{ $package->duration }}">Sewa ↗</a>
                @endif
            </li>
        @endforeach
    </ul>
    @if ($account->status === 'rented' && ($scheduleUrl = $account->rentalScheduleWhatsappUrl()))
        <a class="btn-secondary mt-4 w-full" href="{{ $scheduleUrl }}" target="_blank" rel="noopener noreferrer">Tanya jadwal sewa ↗</a>
    @elseif (! $account->rentalScheduleWhatsappUrl())
        <p class="mt-4 rounded-xl bg-white/5 p-4 text-center text-sm text-slate-400">Kontak WhatsApp belum diatur oleh admin.</p>
    @endif
    <p class="muted mt-4 text-xs">Pilih paket, lalu lanjutkan pemesanan sewa dengan admin melalui WhatsApp.</p>
</div>

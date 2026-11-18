{{-- "30% · tempo 20 Nov" under the status of a booked account; red once the due date has passed. --}}
@if ($account->isBooked() && ($account->dp_percent || $account->payment_due_on))
    @php($daysLeft = $account->daysUntilPaymentDue())
    <span class="mt-1 block text-[11px] {{ $daysLeft !== null && $daysLeft < 0 ? 'font-bold text-rose-300' : 'text-slate-400' }}">{{ implode(' · ', array_filter([$account->dp_percent ? $account->dp_percent.'% dibayar' : null, $account->payment_due_on ? 'tempo '.$account->payment_due_on->locale('id')->translatedFormat('j M') : null])) }}</span>
@endif

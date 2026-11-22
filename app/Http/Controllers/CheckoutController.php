<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Models\Order;
use App\Services\Tripay;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Website checkout (Tripay) for the super admin's accounts: /checkout?akun[]=ID shows the accounts, the buyer's
 * details and the payment channels; posting it creates the order and the Tripay transaction, then sends the buyer
 * to the order page with the payment instructions.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly Tripay $tripay) {}

    public function create(Request $request): View|RedirectResponse
    {
        abort_if($request->user()->is_admin, 403, 'Akun admin tidak bisa checkout. Masuk dengan akun pembeli.');
        [$accounts, $problem] = $this->accounts($request);
        if ($problem) {
            return $problem;
        }
        try {
            $channels = $this->channelsFor((int) $accounts->sum('price'));
        } catch (RuntimeException) {
            $channels = null;
        }

        return view('checkout.create', ['accounts' => $accounts, 'channels' => $channels, 'user' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->is_admin, 403);
        [$accounts, $problem] = $this->accounts($request);
        if ($problem) {
            return $problem;
        }
        $subtotal = (int) $accounts->sum('price');
        try {
            $channels = collect($this->channelsFor($subtotal))->keyBy('code');
        } catch (RuntimeException) {
            return back()->withInput()->with('error', 'Metode pembayaran sedang tidak bisa dimuat. Coba lagi beberapa saat.');
        }
        $data = $request->validate([
            'method' => ['required', Rule::in($channels->keys()->all())],
            'customer_name' => ['required', 'string', 'max:60'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[+\d][\d\s\-]{7,19}$/'],
            'agree' => ['accepted'],
        ], [
            'method.required' => 'Pilih metode pembayaran.',
            'method.in' => 'Metode pembayaran tidak tersedia untuk total ini.',
            'customer_phone.regex' => 'Nomor WhatsApp tidak valid.',
            'agree.accepted' => 'Centang persetujuan Ketentuan Layanan dan Kebijakan Refund.',
        ]);
        $channel = $channels[$data['method']];

        // Hold the accounts: lock them, re-check nobody else is paying for them, then create the order.
        $order = DB::transaction(function () use ($accounts, $request, $data, $channel, $subtotal): ?Order {
            $locked = GameAccount::whereKey($accounts->modelKeys())->lockForUpdate()->get();
            if ($locked->contains(fn (GameAccount $account): bool => $account->status !== 'available' || $account->pendingOrder() !== null)) {
                return null;
            }
            $order = Order::create([
                'merchant_ref' => Order::newMerchantRef(),
                'user_id' => $request->user()->id,
                'status' => 'unpaid',
                'subtotal' => $subtotal,
                'fee' => Tripay::customerFee($channel, $subtotal),
                'total' => $subtotal + Tripay::customerFee($channel, $subtotal),
                'payment_method' => $channel['code'],
                'payment_name' => $channel['name'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $request->user()->email,
                'customer_phone' => preg_replace('/[^\d+]/', '', $data['customer_phone']),
                'expires_at' => now()->addMinutes(max(10, (int) config('services.tripay.expiry_minutes'))),
            ]);
            foreach ($accounts as $account) {
                $order->items()->create(['game_account_id' => $account->id, 'title' => $account->title, 'price' => $account->price]);
            }

            return $order;
        });
        if (! $order) {
            return redirect()->route('cart')->with('error', 'Maaf, salah satu akun baru saja dipesan pembeli lain. Pilih akun lain atau coba lagi nanti.');
        }

        try {
            $transaction = $this->tripay->createTransaction($order);
        } catch (RuntimeException $exception) {
            $order->delete();

            return back()->withInput()->with('error', 'Pembayaran belum bisa dibuat: '.$exception->getMessage());
        }
        $order->update([
            'tripay_reference' => $transaction['reference'] ?? null,
            'pay_code' => $transaction['pay_code'] ?? null,
            'pay_url' => $transaction['checkout_url'] ?? null,
            'qr_url' => $transaction['qr_url'] ?? null,
            'instructions' => $transaction['instructions'] ?? null,
            'fee' => (int) ($transaction['fee_customer'] ?? $order->fee),
            'total' => (int) ($transaction['amount'] ?? $order->total),
            'expires_at' => isset($transaction['expired_time']) ? now()->setTimestamp((int) $transaction['expired_time']) : $order->expires_at,
        ]);

        return redirect()->route('orders.show', $order);
    }

    /**
     * The accounts in ?akun[]= that can be paid on the website, or a redirect explaining why not.
     *
     * @return array{0: Collection<int, GameAccount>, 1: ?RedirectResponse}
     */
    private function accounts(Request $request): array
    {
        $ids = $request->validate(['akun' => ['required', 'array', 'min:1', 'max:20'], 'akun.*' => ['integer', 'min:1']], ['akun.required' => 'Pilih akun yang mau dibeli.'])['akun'];
        $accounts = GameAccount::with(['category:id,name', 'owner'])->whereIn('id', array_unique(array_map(intval(...), $ids)))->forSale()->get();
        $fail = fn (string $message): array => [$accounts, redirect()->route('cart')->with('error', $message)];

        if ($accounts->isEmpty() || $accounts->contains(fn (GameAccount $account): bool => $account->status !== 'available')) {
            return $fail('Akun yang dipilih sudah tidak tersedia.');
        }
        if ($accounts->contains(fn (GameAccount $account): bool => ! $account->usesPaymentGateway())) {
            return $fail('Akun ini dibeli lewat admin/penjual, bukan checkout website.');
        }
        foreach ($accounts as $account) {
            if ($pending = $account->pendingOrder()) {
                if ($pending->user_id === $request->user()->id) {
                    return [$accounts, redirect()->route('orders.show', $pending)->with('success', 'Kamu sudah punya pesanan untuk akun ini. Selesaikan pembayarannya di sini.')];
                }

                return $fail('Akun '.$account->title.' sedang dalam proses pembayaran pembeli lain. Coba lagi nanti.');
            }
        }

        return [$accounts, null];
    }

    /** Channels that accept this total, each with the fee the buyer adds. */
    private function channelsFor(int $amount): array
    {
        return collect($this->tripay->channels())
            ->filter(fn (array $channel): bool => ($channel['min'] === 0 || $amount >= $channel['min']) && ($channel['max'] === 0 || $amount <= $channel['max']))
            ->map(fn (array $channel): array => $channel + ['fee' => Tripay::customerFee($channel, $amount)])
            ->values()->all();
    }
}

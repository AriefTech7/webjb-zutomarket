<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Voucher;
use App\Services\Tripay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Website checkout (Tripay). Two kinds of carts share it:
 *  - /checkout?akun[]=ID: the super admin's game accounts (held for the buyer while they pay);
 *  - /checkout?joki=1: the joki brief prepared by JokiOrderController (kept in the session).
 * The page shows the items, the buyer's details, a voucher box and the payment channels; posting it creates the
 * order and the Tripay transaction, then sends the buyer to the order page with the payment instructions.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly Tripay $tripay) {}

    public function create(Request $request): View|RedirectResponse
    {
        abort_if($request->user()->is_admin, 403, 'Akun admin tidak bisa checkout. Masuk dengan akun pembeli.');
        [$lines, $problem, $query] = $this->lines($request);
        if ($problem) {
            return $problem;
        }
        $subtotal = (int) $lines->sum('price');
        // "Pakai" on the voucher box reloads this page with ?voucher=CODE.
        $code = Voucher::normaliseCode($request->query('voucher'));
        [$voucher, $voucherError] = $this->voucher($code, $subtotal, $request);
        $discount = $voucher?->discountFor($subtotal) ?? 0;
        try {
            $channels = $this->channelsFor($subtotal - $discount);
        } catch (RuntimeException) {
            $channels = null;
        }
        $joki = $request->session()->get(JokiOrderController::SESSION_KEY);

        return view('checkout.create', [
            'lines' => $lines, 'query' => $query, 'channels' => $channels, 'user' => $request->user(),
            'subtotal' => $subtotal, 'voucher' => $voucher, 'voucherCode' => $code, 'voucherError' => $voucherError, 'discount' => $discount,
            // A joki brief already carries the buyer's name and WhatsApp number from the joki form.
            'contactName' => isset($query['joki']) ? ($joki['contact']['name'] ?? null) : null,
            'contactPhone' => isset($query['joki']) ? self::localPhone($joki['contact']['phone'] ?? '') : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->is_admin, 403);
        [$lines, $problem, $query] = $this->lines($request);
        if ($problem) {
            return $problem;
        }
        $subtotal = (int) $lines->sum('price');
        [$voucher, $voucherError] = $this->voucher(Voucher::normaliseCode($request->input('voucher')), $subtotal, $request);
        if ($voucherError) {
            return back()->withInput()->with('error', $voucherError);
        }
        $payable = $subtotal - ($voucher?->discountFor($subtotal) ?? 0);
        try {
            $channels = collect($this->channelsFor($payable))->keyBy('code');
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
        $accountIds = $lines->pluck('account')->filter()->map->getKey()->values()->all();

        // Hold the accounts: lock them, re-check nobody else is paying for them, then create the order.
        $order = DB::transaction(function () use ($lines, $accountIds, $request, $data, $channel, $subtotal, $voucher, $payable): Order|string|null {
            if ($accountIds) {
                $locked = GameAccount::whereKey($accountIds)->lockForUpdate()->get();
                if ($locked->contains(fn (GameAccount $account): bool => $account->status !== 'available' || $account->pendingOrder() !== null)) {
                    return null;
                }
            }
            // Re-check the voucher under a lock so its quota cannot be overspent by two buyers at once.
            if ($voucher && ($problem = Voucher::whereKey($voucher->id)->lockForUpdate()->first()?->problemFor($subtotal, $request->user()))) {
                return $problem;
            }
            $order = Order::create([
                'merchant_ref' => Order::newMerchantRef(),
                'user_id' => $request->user()->id,
                'status' => 'unpaid',
                'subtotal' => $subtotal,
                'voucher_id' => $voucher?->id,
                'voucher_code' => $voucher?->code,
                'discount' => $subtotal - $payable,
                'fee' => Tripay::customerFee($channel, $payable),
                'total' => $payable + Tripay::customerFee($channel, $payable),
                'payment_method' => $channel['code'],
                'payment_name' => $channel['name'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $request->user()->email,
                'customer_phone' => preg_replace('/[^\d+]/', '', $data['customer_phone']),
                'expires_at' => now()->addMinutes(max(10, (int) config('services.tripay.expiry_minutes'))),
            ]);
            foreach ($lines as $line) {
                $order->items()->create(['game_account_id' => $line['account']?->id, 'kind' => $line['kind'], 'title' => $line['title'], 'price' => $line['price'], 'details' => $line['details']]);
            }

            return $order;
        });
        if (! $order) {
            return redirect()->route('cart')->with('error', 'Maaf, salah satu akun baru saja dipesan pembeli lain. Pilih akun lain atau coba lagi nanti.');
        }
        if (is_string($order)) {
            return back()->withInput()->with('error', $order);
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
        if (isset($query['joki'])) {
            $request->session()->forget(JokiOrderController::SESSION_KEY);
        }

        return redirect()->route('orders.show', $order);
    }

    /**
     * The voucher for a typed code, or why it cannot be used.
     *
     * @return array{0: ?Voucher, 1: ?string}
     */
    private function voucher(string $code, int $subtotal, Request $request): array
    {
        if ($code === '') {
            return [null, null];
        }
        $voucher = Voucher::findByCode($code);
        if (! $voucher) {
            return [null, 'Kode voucher tidak ditemukan.'];
        }
        $problem = $voucher->problemFor($subtotal, $request->user());

        return $problem ? [null, $problem] : [$voucher, null];
    }

    /**
     * What is being bought (joki brief or game accounts), or a redirect explaining why it cannot be paid here,
     * plus the query that identifies this cart for the voucher box.
     *
     * @return array{0: Collection<int, array{account: ?GameAccount, kind: string, title: string, price: int, category: string, image: ?string, url: ?string, details: ?array}>, 1: ?RedirectResponse, 2: array<string, mixed>}
     */
    private function lines(Request $request): array
    {
        if ($request->boolean('joki')) {
            $joki = $request->session()->get(JokiOrderController::SESSION_KEY);
            if (! Tripay::active() || ! is_array($joki)) {
                return [collect(), redirect()->route('joki')->with('error', 'Isi form joki dulu, lalu tekan "Bayar di website".'), []];
            }

            return [collect([[
                'account' => null, 'kind' => 'joki', 'title' => $joki['title'], 'price' => (int) $joki['price'], 'category' => 'Joki Mobile Legends',
                'image' => asset('images/joki/mobile-legends.webp'), 'url' => route('joki'), 'details' => $joki['details'],
            ]]), null, ['joki' => 1]];
        }

        [$accounts, $problem] = $this->accounts($request);

        return [$accounts->map(fn (GameAccount $account): array => [
            'account' => $account, 'kind' => 'account', 'title' => $account->title, 'price' => (int) $account->price, 'category' => $account->category->name,
            'image' => $account->imageUrl(), 'url' => route('accounts.show', $account->slug), 'details' => null,
        ])->values(), $problem, ['akun' => $accounts->modelKeys()]];
    }

    /**
     * The accounts in ?akun[]= that can be paid on the website, or a redirect explaining why not.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Collection<int, GameAccount>, 1: ?RedirectResponse}
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

    /** "+62 812-…", "62812…", "812…" → "0812…" for the checkout's WhatsApp field. */
    private static function localPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return $digits === '' ? null : '0'.preg_replace('/^(?:62|0)/', '', $digits);
    }
}

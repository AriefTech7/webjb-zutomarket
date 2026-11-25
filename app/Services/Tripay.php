<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin client for the Tripay API (https://tripay.co.id/developer). Keys come from config/services.php; the
 * private key never leaves the server. Who pays the fee is set per payment channel in the Tripay dashboard: the
 * fee shown at checkout is the channel's fee_customer, and the final total is whatever Tripay returns.
 */
class Tripay
{
    public static function enabled(): bool
    {
        return filled(config('services.tripay.api_key')) && filled(config('services.tripay.private_key')) && filled(config('services.tripay.merchant_code'));
    }

    /**
     * Website checkout is offered: the keys are set and the super admin has not switched it off in
     * Admin > Pesanan website. When off, every "Beli" button goes back to WhatsApp; orders already made still finish.
     */
    public static function active(): bool
    {
        return self::enabled() && Setting::valueFor('payment_gateway', '1') === '1';
    }

    public static function sandbox(): bool
    {
        return config('services.tripay.mode') !== 'production';
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(self::sandbox() ? 'https://tripay.co.id/api-sandbox/' : 'https://tripay.co.id/api/')
            ->withToken((string) config('services.tripay.api_key'))->acceptJson()->timeout(20);
    }

    /**
     * Active payment channels, cached for an hour.
     *
     * @return list<array{code: string, name: string, group: string, icon: string, flat: int, percent: float, min: int, max: int}>
     */
    public function channels(): array
    {
        return Cache::remember('tripay.channels.'.(self::sandbox() ? 'sandbox' : 'production'), now()->addHour(), function (): array {
            $response = $this->http()->get('merchant/payment-channel');
            if (! $response->successful() || ! $response->json('success')) {
                Log::warning('Tripay payment-channel failed', ['status' => $response->status(), 'message' => $response->json('message')]);
                throw new RuntimeException('Metode pembayaran belum bisa dimuat.');
            }

            return collect($response->json('data', []))->filter(fn (array $channel): bool => (bool) ($channel['active'] ?? false))
                ->map(fn (array $channel): array => [
                    'code' => (string) $channel['code'],
                    'name' => (string) $channel['name'],
                    'group' => (string) ($channel['group'] ?? 'Lainnya'),
                    'icon' => (string) ($channel['icon_url'] ?? ''),
                    'flat' => (int) ($channel['fee_customer']['flat'] ?? 0),
                    'percent' => (float) ($channel['fee_customer']['percent'] ?? 0),
                    'min' => (int) ($channel['minimum_amount'] ?? 0),
                    'max' => (int) ($channel['maximum_amount'] ?? 0),
                ])->values()->all();
        });
    }

    /** The fee the buyer pays on top for this channel (Tripay rounds the percentage part up). */
    public static function customerFee(array $channel, int $amount): int
    {
        return $channel['flat'] + (int) ceil($amount * $channel['percent'] / 100);
    }

    /**
     * Item prices after taking $discount off in proportion to each price; the rounding remainder goes on the last
     * item so the prices add up to exactly sum(prices) - discount.
     *
     * @param  list<int>  $prices
     * @return list<int>
     */
    public static function splitDiscount(array $prices, int $discount): array
    {
        $total = array_sum($prices);
        if ($discount <= 0 || $total <= 0) {
            return array_values($prices);
        }
        $result = [];
        $left = $discount;
        foreach (array_values($prices) as $index => $price) {
            $share = $index === count($prices) - 1 ? $left : (int) floor($discount * $price / $total);
            $share = min($share, $price);
            $result[] = $price - $share;
            $left -= $share;
        }

        return $result;
    }

    public static function signature(string $merchantRef, int $amount): string
    {
        return hash_hmac('sha256', config('services.tripay.merchant_code').$merchantRef.$amount, (string) config('services.tripay.private_key'));
    }

    public static function validCallbackSignature(string $rawBody, ?string $signature): bool
    {
        return filled($signature) && self::enabled() && hash_equals(hash_hmac('sha256', $rawBody, (string) config('services.tripay.private_key')), (string) $signature);
    }

    /**
     * Create the closed-payment transaction for an order and return Tripay's data (reference, pay_code, qr_url,
     * checkout_url, amount, fee_customer, expired_time, instructions…).
     */
    public function createTransaction(Order $order): array
    {
        $order->loadMissing('items');
        $amount = $order->payable();
        $prices = self::splitDiscount($order->items->pluck('price')->all(), $order->discount);
        $response = $this->http()->asForm()->post('transaction/create', [
            'method' => $order->payment_method,
            'merchant_ref' => $order->merchant_ref,
            'amount' => $amount,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            // Tripay checks that the items add up to the amount, so a voucher discount is spread over the items.
            'order_items' => $order->items->values()->map(fn ($item, int $index): array => array_filter([
                'sku' => 'AKUN-'.$item->game_account_id,
                'name' => mb_substr($item->title, 0, 100).($order->discount ? ' (voucher '.$order->voucher_code.')' : ''),
                'price' => $prices[$index],
                'quantity' => 1,
                'product_url' => $item->account ? route('accounts.show', $item->account->slug) : null,
            ], fn ($value): bool => $value !== null))->all(),
            'callback_url' => route('payment.tripay.callback'),
            'return_url' => route('orders.show', $order),
            'expired_time' => $order->expires_at->getTimestamp(),
            'signature' => self::signature($order->merchant_ref, $amount),
        ]);
        if (! $response->successful() || ! $response->json('success')) {
            Log::warning('Tripay transaction/create failed', ['ref' => $order->merchant_ref, 'status' => $response->status(), 'message' => $response->json('message')]);
            throw new RuntimeException($response->json('message') ?: 'Transaksi belum bisa dibuat.');
        }

        return $response->json('data');
    }

    /** Current Tripay status of a transaction ("UNPAID", "PAID", "EXPIRED", "FAILED", "REFUND"), or null. */
    public function status(string $reference): ?string
    {
        $response = $this->http()->get('transaction/detail', ['reference' => $reference]);

        return $response->successful() && $response->json('success') ? $response->json('data.status') : null;
    }
}

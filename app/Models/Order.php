<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A website checkout paid through Tripay. "unpaid" orders hold their accounts until expires_at; Tripay's callback
 * (or a status check from the order page) moves them to paid / expired / failed / refunded.
 */
class Order extends Model
{
    public const STATUSES = ['unpaid' => 'Menunggu pembayaran', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'failed' => 'Gagal', 'refunded' => 'Dikembalikan'];

    protected $fillable = ['merchant_ref', 'user_id', 'status', 'subtotal', 'voucher_id', 'voucher_code', 'discount', 'fee', 'total', 'payment_method', 'payment_name', 'tripay_reference', 'pay_code', 'pay_url', 'qr_url', 'instructions', 'customer_name', 'customer_email', 'customer_phone', 'expires_at', 'paid_at', 'delivered_at', 'checked_at', 'admin_note'];

    protected function casts(): array
    {
        return ['subtotal' => 'integer', 'discount' => 'integer', 'fee' => 'integer', 'total' => 'integer', 'instructions' => 'array', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'delivered_at' => 'datetime', 'checked_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'merchant_ref';
    }

    /** "ZM2611221530AB12CD": readable, unique enough, and safe in URLs and Tripay's merchant_ref. */
    public static function newMerchantRef(): string
    {
        do {
            $ref = 'ZM'.now()->timezone(GameAccount::LOCAL_TIMEZONE)->format('ymdHi').Str::upper(Str::random(6));
        } while (static::where('merchant_ref', $ref)->exists());

        return $ref;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return BelongsTo<Voucher, $this> */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /** What the accounts cost after the voucher, before the payment fee (the amount sent to Tripay). */
    public function payable(): int
    {
        return max(0, $this->subtotal - $this->discount);
    }

    /**
     * Unpaid and still inside the payment window: its accounts are held.
     *
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'unpaid')->where('expires_at', '>', now());
    }

    public function isPending(): bool
    {
        return $this->status === 'unpaid' && $this->expires_at?->isFuture();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /** Tripay status → ours. Unknown statuses leave the order alone. */
    public function applyTripayStatus(string $tripayStatus): void
    {
        match (strtoupper($tripayStatus)) {
            'PAID' => $this->markPaid(),
            'EXPIRED' => $this->status === 'unpaid' ? $this->update(['status' => 'expired']) : null,
            'FAILED' => $this->status === 'unpaid' ? $this->update(['status' => 'failed']) : null,
            'REFUND' => $this->update(['status' => 'refunded']),
            default => null,
        };
    }

    /**
     * Paid: the accounts become "Terjual". If one was sold some other way in the meantime, the admin is told to
     * refund or replace it (admin_note).
     */
    public function markPaid(): void
    {
        if ($this->status === 'paid') {
            return;
        }
        DB::transaction(function (): void {
            $conflicts = [];
            foreach ($this->items()->with('account')->get() as $item) {
                $account = $item->account;
                if (! $account) {
                    $conflicts[] = $item->title.' (sudah dihapus)';
                } elseif ($account->status !== 'available') {
                    $conflicts[] = $item->title.' (status '.$account->statusLabel().')';
                } else {
                    $account->forceFill(['status' => 'sold', 'sold_at' => now()])->save();
                }
            }
            $this->forceFill([
                'status' => 'paid',
                'paid_at' => $this->paid_at ?? now(),
                'admin_note' => $conflicts ? 'Perlu dicek: '.implode(', ', $conflicts).' — refund atau ganti akun.' : $this->admin_note,
            ])->save();
        });
    }
}

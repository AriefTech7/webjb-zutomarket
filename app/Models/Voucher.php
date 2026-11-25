<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A discount code buyers type at the website checkout. "percent" takes value% off (capped by max_discount),
 * "fixed" takes value rupiah off. A use counts while its order is paid or still waiting for payment.
 */
class Voucher extends Model
{
    public const TYPES = ['percent' => 'Persen (%)', 'fixed' => 'Potongan rupiah'];

    /** Whatever the voucher, the buyer still pays at least this much (payment channels have a minimum). */
    public const MIN_PAYABLE = 10000;

    /** Same defaults as the table, so a fresh model behaves like a saved one. */
    protected $attributes = ['is_active' => true, 'min_purchase' => 0, 'per_user' => 1];

    protected $fillable = ['code', 'type', 'value', 'max_discount', 'min_purchase', 'quota', 'per_user', 'starts_at', 'ends_at', 'is_active', 'note'];

    protected function casts(): array
    {
        return ['value' => 'integer', 'max_discount' => 'integer', 'min_purchase' => 'integer', 'quota' => 'integer', 'per_user' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public static function normaliseCode(?string $code): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $code));
    }

    public static function findByCode(?string $code): ?self
    {
        $code = self::normaliseCode($code);

        return $code === '' ? null : static::where('code', $code)->first();
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Orders that use up the voucher: paid, or unpaid and still inside the payment window.
     *
     * @return HasMany<Order, $this>
     */
    public function countedOrders(): HasMany
    {
        return $this->orders()->where(fn (Builder $query) => $query->where('status', 'paid')->orWhere(fn (Builder $q) => $q->where('status', 'unpaid')->where('expires_at', '>', now())));
    }

    public function discountFor(int $subtotal): int
    {
        $discount = $this->type === 'percent' ? (int) floor($subtotal * min(100, $this->value) / 100) : $this->value;
        if ($this->type === 'percent' && $this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return max(0, min($discount, $subtotal));
    }

    /** Why this buyer cannot use the voucher on this subtotal, or null when they can. */
    public function problemFor(int $subtotal, User $user): ?string
    {
        $now = now();
        if (! $this->is_active || ($this->starts_at && $this->starts_at->isAfter($now))) {
            return 'Kode voucher tidak berlaku.';
        }
        if ($this->ends_at && $this->ends_at->isBefore($now)) {
            return 'Kode voucher sudah kedaluwarsa.';
        }
        if ($subtotal < $this->min_purchase) {
            return 'Voucher ini berlaku untuk pembelian minimal '.Order::rupiah($this->min_purchase).'.';
        }
        if ($this->quota !== null && $this->countedOrders()->count() >= $this->quota) {
            return 'Kuota voucher ini sudah habis.';
        }
        if ($this->per_user !== null && $this->countedOrders()->where('user_id', $user->id)->count() >= $this->per_user) {
            return 'Kamu sudah memakai voucher ini.';
        }
        if ($subtotal - $this->discountFor($subtotal) < self::MIN_PAYABLE) {
            return 'Voucher ini tidak bisa dipakai untuk pesanan ini (total bayar minimal '.Order::rupiah(self::MIN_PAYABLE).').';
        }

        return null;
    }

    /** "10% (maks Rp 50.000)" or "Rp 25.000" for the admin list. */
    public function label(): string
    {
        return $this->type === 'percent'
            ? $this->value.'%'.($this->max_discount ? ' (maks '.Order::rupiah($this->max_discount).')' : '')
            : Order::rupiah($this->value);
    }
}

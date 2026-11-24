<?php

namespace App\Models;

use App\Services\Tripay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class GameAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['category_id', 'title', 'slug', 'price', 'description', 'specifications', 'main_image', 'status', 'dp_percent', 'payment_due_on', 'seller_whatsapp', 'original_price', 'installment_dp_percent', 'installment_days', 'installment_enabled', 'card_note', 'flash_sale_ends_at', 'is_rental', 'ready_estimate_at'];

    /** Admins type flash sale times in Indonesian Western time. */
    public const LOCAL_TIMEZONE = 'Asia/Jakarta';

    /** Booked by a buyer who paid a down payment or is paying in instalments: shown publicly but no longer for sale. */
    public const BOOKED_STATUSES = ['dp', 'cicil'];

    /** Statuses of non-rental accounts that the public catalog shows. */
    public const PUBLIC_SALE_STATUSES = ['available', 'dp', 'cicil', 'sold'];

    public const STATUS_LABELS = ['available' => 'Tersedia', 'dp' => 'Sudah DP', 'cicil' => 'Sudah dicicil', 'sold' => 'Terjual', 'draft' => 'Draft', 'rented' => 'Disewa'];

    public function isBooked(): bool
    {
        return in_array($this->status, self::BOOKED_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * The store no longer sells on instalments (2026-11-23, payment gateway terms). Accounts still booked under the
     * old DP / cicil arrangement are shown to visitors as "Dipesan"; the DP details stay admin-only.
     */
    public function publicStatusLabel(): string
    {
        return $this->isBooked() ? 'Dipesan' : $this->statusLabel();
    }

    /**
     * The admin's short card note, unless it advertises something the store no longer offers (payment gateway
     * terms): instalments ("Bisa cicil", "DP 30%"), trade-ins ("Bisa TT", "tukar tambah") or haggling ("Nego").
     */
    public function publicCardNote(): ?string
    {
        $note = trim((string) $this->card_note);

        return $note !== '' && ! preg_match('/cicil|\bdp\b|kredit|angsur|uang muka|\btt\b|tukar|\bnego|tawar/i', $note) ? $note : null;
    }

    /** Rupiah already paid on a booked account (DP / instalments so far). */
    public function paidAmount(): int
    {
        return (int) round($this->price * ($this->dp_percent ?? 0) / 100);
    }

    public function remainingAmount(): int
    {
        return max(0, $this->price - $this->paidAmount());
    }

    /** Days until the balance is due in Jakarta time: negative when overdue, null without a due date. */
    public function daysUntilPaymentDue(): ?int
    {
        if (! $this->payment_due_on) {
            return null;
        }
        $today = now(self::LOCAL_TIMEZONE)->startOfDay();

        return (int) $today->diffInDays($this->payment_due_on->copy()->shiftTimezone(self::LOCAL_TIMEZONE)->startOfDay(), false);
    }

    protected function casts(): array
    {
        return ['price' => 'integer', 'original_price' => 'integer', 'dp_percent' => 'integer', 'payment_due_on' => 'date', 'installment_dp_percent' => 'integer', 'installment_days' => 'integer', 'installment_enabled' => 'boolean', 'sold_at' => 'datetime', 'flash_sale_ends_at' => 'datetime', 'is_rental' => 'boolean', 'ready_estimate_at' => 'datetime'];
    }

    /** 950 → "950", 3100 → "3,1 rb", 1250000 → "1,3 jt" (the "Dilihat" / "Suka" counters). */
    public static function compactCount(int $count): string
    {
        foreach ([1_000_000 => 'jt', 1_000 => 'rb'] as $step => $unit) {
            if ($count >= $step) {
                return str_replace('.', ',', rtrim(rtrim(number_format($count / $step, 1, '.', ''), '0'), '.')).' '.$unit;
            }
        }

        return (string) $count;
    }

    /**
     * Paid on the website through Tripay instead of WhatsApp: every account the super admin owns (rentals
     * excepted), once Tripay is configured. Other admins' accounts stay on WhatsApp. Loads the owner if needed.
     */
    public function usesPaymentGateway(): bool
    {
        return Tripay::enabled() && ! $this->is_rental && (bool) $this->owner?->is_super_admin;
    }

    /** The unpaid, not yet expired order holding this account, if any. */
    public function pendingOrder(): ?Order
    {
        return Order::pending()->whereHas('items', fn ($query) => $query->where('game_account_id', $this->id))->latest('id')->first();
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Accounts this admin may see and edit in the admin area: all for the super admin, otherwise their own.
     *
     * @param  Builder<self>  $query
     */
    public function scopeManageableBy(Builder $query, User $admin): void
    {
        if (! $admin->is_super_admin) {
            $query->where('owner_id', $admin->id);
        }
    }

    public function rentalPackages(): HasMany
    {
        return $this->hasMany(RentalPackage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Keep only accounts that are sold, not rented out.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForSale(Builder $query): void
    {
        $query->where('is_rental', false);
    }

    /**
     * Keep only public rental accounts, whether ready or currently rented.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRental(Builder $query): void
    {
        $query->where('is_rental', true)->whereIn('status', ['available', 'rented']);
    }

    public function cheapestRentalPackage(): ?RentalPackage
    {
        return $this->rentalPackages->sortBy('price')->first();
    }

    public function rentalWhatsappUrl(RentalPackage $package): ?string
    {
        return $this->whatsappLink('Halo admin, saya ingin menyewa akun '.$this->title.' paket '.$package->duration.' seharga '.$package->formattedPrice().'. '.route('accounts.show', $this->slug));
    }

    public function rentalScheduleWhatsappUrl(): ?string
    {
        return $this->whatsappLink('Halo admin, saya ingin menanyakan jadwal sewa akun '.$this->title.'. '.route('accounts.show', $this->slug), requireAvailable: false);
    }

    /**
     * Keep only available accounts whose flash sale has not ended yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOnFlashSale(Builder $query): void
    {
        $query->where('status', 'available')->where('is_rental', false)->where('flash_sale_ends_at', '>', now());
    }

    public function isOnFlashSale(): bool
    {
        return $this->status === 'available' && $this->flash_sale_ends_at?->isFuture() === true;
    }

    public function hasDiscount(): bool
    {
        return $this->original_price !== null && $this->original_price > $this->price;
    }

    public function discountPercent(): int
    {
        return $this->hasDiscount() ? (int) round((1 - $this->price / $this->original_price) * 100) : 0;
    }

    public function formattedSavings(): string
    {
        return 'Rp '.number_format(max(0, (int) $this->original_price - $this->price), 0, ',', '.');
    }

    public function buyWhatsappUrl(): ?string
    {
        return $this->whatsappLink('Halo admin, saya ingin membeli akun '.$this->title.' seharga '.$this->formattedPrice().'. '.route('accounts.show', $this->slug));
    }

    public function formattedOriginalPrice(): string
    {
        return 'Rp '.number_format((int) $this->original_price, 0, ',', '.');
    }

    /** Remaining flash sale time in the same format the browser countdown uses. */
    public function flashSaleCountdown(): string
    {
        $seconds = $this->flash_sale_ends_at ? max(0, (int) now()->diffInSeconds($this->flash_sale_ends_at, false)) : 0;
        if ($seconds === 0) {
            return 'Berakhir';
        }
        $clock = sprintf('%02d:%02d:%02d', intdiv($seconds % 86400, 3600), intdiv($seconds % 3600, 60), $seconds % 60);

        return $seconds >= 86400 ? intdiv($seconds, 86400).' hari '.$clock : $clock;
    }

    protected static function booted(): void
    {
        static::saving(function (GameAccount $account): void {
            if ($account->isDirty('status')) {
                $account->sold_at = $account->status === 'sold' ? now() : null;
            }
            // Payment progress only means something while the account is booked.
            if (! $account->isBooked()) {
                $account->dp_percent = null;
                $account->payment_due_on = null;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GameAccountImage::class);
    }

    /**
     * Keep only accounts that match every keyword in the search text.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        foreach (preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $keyword) {
            $query->where(function (Builder $q) use ($keyword): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $keyword).'%';
                $q->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhere('specifications', 'like', $term)->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $term));
            });
        }
    }

    public function imageUrl(): string
    {
        return str_starts_with($this->main_image, 'demo/') || str_starts_with($this->main_image, 'images/') ? asset($this->main_image) : Storage::disk('public')->url($this->main_image);
    }

    public function formattedPrice(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }

    public function whatsappUrl(): ?string
    {
        return $this->whatsappLink(strtr(Setting::valueFor('whatsapp_default_message', 'Halo admin, saya tertarik dengan akun {nama_akun} seharga {harga}. Apakah masih tersedia?'), [
            '{nama_akun}' => $this->title,
            '{harga}' => $this->formattedPrice(),
            '{url}' => route('accounts.show', $this->slug),
        ]));
    }

    /** WhatsApp number that handles this account: its own seller, else the store (rental number for rentals). */
    public function contactNumber(): string
    {
        return $this->seller_whatsapp ?: ($this->is_rental ? Setting::valueFor('whatsapp_rental_number') : '') ?: Setting::valueFor('whatsapp_number');
    }

    private function whatsappLink(string $message, bool $requireAvailable = true): ?string
    {
        $number = $this->contactNumber();
        if (! $number || ($requireAvailable && $this->status !== 'available')) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}

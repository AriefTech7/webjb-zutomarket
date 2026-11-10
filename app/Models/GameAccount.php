<?php

namespace App\Models;

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

    protected $fillable = ['category_id', 'title', 'slug', 'price', 'description', 'specifications', 'main_image', 'status', 'seller_whatsapp', 'original_price', 'flash_sale_ends_at', 'is_rental', 'ready_estimate_at'];

    /** Admins type flash sale times in Indonesian Western time. */
    public const LOCAL_TIMEZONE = 'Asia/Jakarta';

    protected function casts(): array
    {
        return ['price' => 'integer', 'original_price' => 'integer', 'sold_at' => 'datetime', 'flash_sale_ends_at' => 'datetime', 'is_rental' => 'boolean', 'ready_estimate_at' => 'datetime'];
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

    public function tradeInWhatsappUrl(): ?string
    {
        return $this->whatsappLink('Halo admin, saya ingin mengajukan tukar tambah untuk akun '.$this->title.' seharga '.$this->formattedPrice().'. '.route('accounts.show', $this->slug).' Akun yang saya tawarkan: ...');
    }

    public function installmentWhatsappUrl(): ?string
    {
        return $this->whatsappLink('Halo admin, saya ingin mengajukan cicilan untuk akun '.$this->title.' seharga '.$this->formattedPrice().'. '.route('accounts.show', $this->slug).' Mohon info DP dan tenor yang tersedia.');
    }

    private function whatsappLink(string $message, bool $requireAvailable = true): ?string
    {
        $number = $this->seller_whatsapp ?: ($this->is_rental ? Setting::valueFor('whatsapp_rental_number') : '') ?: Setting::valueFor('whatsapp_number');
        if (! $number || ($requireAvailable && $this->status !== 'available')) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}

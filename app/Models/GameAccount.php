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

    protected $fillable = ['category_id', 'title', 'slug', 'price', 'description', 'specifications', 'main_image', 'status', 'seller_whatsapp'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'sold_at' => 'datetime'];
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

    private function whatsappLink(string $message): ?string
    {
        $number = $this->seller_whatsapp ?: Setting::valueFor('whatsapp_number');
        if (! $number || $this->status !== 'available') {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}

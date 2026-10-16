<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class GameAccount extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'title', 'slug', 'price', 'description', 'specifications', 'main_image', 'status'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GameAccountImage::class);
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
        $number = Setting::valueFor('whatsapp_number');
        if (! $number || $this->status !== 'available') {
            return null;
        }
        $message = strtr(Setting::valueFor('whatsapp_default_message', 'Halo admin, saya tertarik dengan akun {nama_akun} seharga {harga}. Apakah masih tersedia?'), [
            '{nama_akun}' => $this->title,
            '{harga}' => $this->formattedPrice(),
            '{url}' => route('accounts.show', $this->slug),
        ]);

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}

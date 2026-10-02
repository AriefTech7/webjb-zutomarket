<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradeRequest extends Model
{
    protected $fillable = ['category_id', 'name', 'whatsapp_number', 'email', 'description', 'estimated_price', 'desired_account', 'status', 'admin_note'];

    protected function casts(): array
    {
        return ['estimated_price' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TradeRequestImage::class);
    }

    public function formattedEstimatedPrice(): string
    {
        return $this->estimated_price ? 'Rp '.number_format($this->estimated_price, 0, ',', '.') : '-';
    }

    public function whatsappUrl(): string
    {
        return 'https://wa.me/'.$this->whatsapp_number.'?text='.rawurlencode('Halo '.$this->name.', pengajuan tukar tambah akun game Anda di SHOPGAME ZUTO sudah kami terima.');
    }
}

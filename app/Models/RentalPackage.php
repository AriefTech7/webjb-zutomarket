<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPackage extends Model
{
    use HasFactory;

    protected $fillable = ['duration', 'price', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'sort_order' => 'integer'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GameAccount::class, 'game_account_id');
    }

    public function formattedPrice(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }
}

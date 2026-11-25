<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One game account in an order. Title and price are copied so the order still reads right if the account changes. */
class OrderItem extends Model
{
    protected $fillable = ['order_id', 'game_account_id', 'kind', 'title', 'price', 'details'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'details' => 'array'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<GameAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(GameAccount::class, 'game_account_id');
    }
}

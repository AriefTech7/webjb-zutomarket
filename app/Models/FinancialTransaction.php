<?php

namespace App\Models;

use Database\Factories\FinancialTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialTransaction extends Model
{
    /** @use HasFactory<FinancialTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'type', 'submission_id', 'occurred_on', 'game_account_id', 'received_account_id', 'trade_request_id',
        'active_sale_account_id', 'active_trade_request_id', 'purchase_account_id',
        'reversal_of_id', 'user_id', 'description', 'received_title', 'category_name', 'revenue',
        'cost_of_goods', 'operating_expense', 'cash_in', 'cash_out', 'trade_value', 'sold_units', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date', 'revenue' => 'integer', 'cost_of_goods' => 'integer',
            'operating_expense' => 'integer', 'cash_in' => 'integer', 'cash_out' => 'integer',
            'trade_value' => 'integer', 'sold_units' => 'integer',
        ];
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function label(): string
    {
        return ['sale' => 'Penjualan', 'trade_in' => 'Tukar tambah', 'purchase' => 'Pembelian stok', 'expense' => 'Biaya operasional', 'reversal' => 'Pembatalan / refund'][$this->type];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualSale extends Model
{
    use HasFactory;

    /** @var array<string, string> Types admins can pick when recording a sale. */
    public const TYPES = ['joki' => 'Joki', 'rental' => 'Rental', 'jual_akun' => 'Jual akun', 'rekber' => 'Rekber'];

    protected $fillable = ['type', 'item', 'amount', 'sold_on', 'note'];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Lainnya';
    }

    protected function casts(): array
    {
        return ['amount' => 'integer', 'sold_on' => 'date'];
    }
}

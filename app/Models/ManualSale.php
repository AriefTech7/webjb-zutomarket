<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualSale extends Model
{
    use HasFactory;

    protected $fillable = ['item', 'amount', 'sold_on', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'sold_on' => 'date'];
    }
}

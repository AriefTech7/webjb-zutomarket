<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function valueFor(string $key, string $default = ''): string
    {
        if ($key === 'store_name' && $default === '') {
            $default = 'SHOPGAME ZUTO';
        }

        return static::where('key', $key)->value('value') ?? $default;
    }
}

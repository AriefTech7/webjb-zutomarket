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

    public const DEFAULT_RUNNING_TEXT = "Jual beli akun game langsung dengan admin via WhatsApp\nBisa cicil & tukar tambah, tanya admin\nRental akun tersedia, cek halaman Rental\nJoki rank Mobile Legends, pesan di menu Joki ML";

    /**
     * Items for the running text bar under the header: one per non-empty line of the setting.
     *
     * @return list<string>
     */
    public static function runningTextItems(): array
    {
        $lines = explode("\n", static::valueFor('running_text', self::DEFAULT_RUNNING_TEXT));

        return array_values(array_filter(array_map(trim(...), $lines), fn (string $line): bool => $line !== ''));
    }
}

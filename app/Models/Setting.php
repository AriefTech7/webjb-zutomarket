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

    /*
     * Instalment (DP) terms, edited in Admin > Dashboard / Pengaturan and shown on /cicilan. They apply from the
     * start with these defaults; an admin who empties a field turns that part off.
     */
    public const DEFAULT_DP_PERCENT = '30';

    /** Balance due within "days" after the DP, by account price. "max" null = that price and above. */
    public const DEFAULT_DUE_TIERS = [['max' => 100000, 'days' => 7], ['max' => 300000, 'days' => 16], ['max' => 500000, 'days' => 23], ['max' => null, 'days' => 31]];

    /** One rule per line; {dp} becomes the DP percentage and **text** is shown bold. */
    public const DEFAULT_DP_RULES = "DP wajib {dp}% dari harga akun.\nStatus akun berubah menjadi SUDAH DP setelah pembayaran dikonfirmasi admin, lalu TERJUAL setelah lunas.\nPelunasan mengikuti batas waktu cicilan.\nDP {dp}% sudah bisa login & open tt + cicil juga.";

    public const DEFAULT_DP_CANCEL_RULES = "**Pembatalan / lewat batas waktu:** jika pelunasan melewati batas hari yang ditentukan atau pembeli batal secara sepihak, **maka dikenakan potongan 50% dari harga akun.** Akun ditarik kembali dan sisa dana (jika ada setelah dipotong) baru dikembalikan.\n**Penarikan akun:** jika dalam 10 hari tidak membayar cicilan atau tidak menyicil sedikit², akun langsung ditarik.\nPastikan data pesanan sebelum melakukan pembayaran.";

    public static function downPaymentPercent(): ?int
    {
        $value = static::valueFor('dp_min_percent', self::DEFAULT_DP_PERCENT);

        return $value === '' ? null : (int) $value;
    }

    /**
     * Price tiers sorted by price, each with the lowest price it covers ("min").
     *
     * @return list<array{min: int, max: ?int, days: int}>
     */
    public static function dueTiers(): array
    {
        $stored = json_decode(static::valueFor('dp_due_tiers', (string) json_encode(self::DEFAULT_DUE_TIERS)), true);
        $tiers = collect(is_array($stored) ? $stored : [])
            ->filter(fn ($tier): bool => is_array($tier) && (int) ($tier['days'] ?? 0) > 0)
            ->sortBy(fn (array $tier): int => isset($tier['max']) ? (int) $tier['max'] : PHP_INT_MAX)->values();
        $min = 1000;

        return $tiers->map(function (array $tier) use (&$min): array {
            $row = ['min' => $min, 'max' => isset($tier['max']) ? (int) $tier['max'] : null, 'days' => (int) $tier['days']];
            $min = ($row['max'] ?? $min) + 1000;

            return $row;
        })->all();
    }

    /** Days to pay off an account of this price, or null when no tier covers it. */
    public static function dueDaysFor(int $price): ?int
    {
        foreach (static::dueTiers() as $tier) {
            if ($tier['max'] === null || $price <= $tier['max']) {
                return $tier['days'];
            }
        }

        return null;
    }

    public static function downPaymentAmount(int $price): int
    {
        return (int) ceil($price * (static::downPaymentPercent() ?? 0) / 100);
    }

    /**
     * Rule lines of "dp_rules" or "dp_cancel_rules" as safe HTML: escaped, {dp} filled in, **bold** kept.
     *
     * @return list<string>
     */
    public static function ruleLines(string $key): array
    {
        $default = $key === 'dp_cancel_rules' ? self::DEFAULT_DP_CANCEL_RULES : self::DEFAULT_DP_RULES;
        $lines = array_filter(array_map(trim(...), explode("\n", static::valueFor($key, $default))), fn (string $line): bool => $line !== '');

        return array_values(array_map(fn (string $line): string => preg_replace('/\*\*(.+?)\*\*/u', '<strong class="text-white">$1</strong>',
            e(str_replace('{dp}', (string) (static::downPaymentPercent() ?? ''), $line))), $lines));
    }

    public const DEFAULT_RUNNING_TEXT = "Jual beli akun game langsung dengan admin via WhatsApp\nTanya stok langsung lewat live chat\nRental akun tersedia, cek halaman Rental\nJoki rank Mobile Legends, pesan di menu Joki ML";

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

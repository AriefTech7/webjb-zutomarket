<?php

namespace App\Services;

/**
 * Server-side copy of the joki calculator in resources/js/joki.js, so a joki paid on the website is priced from
 * config/joki.php and never from what the browser sends. Keep both in step (tests cover the same examples).
 */
class JokiPricing
{
    private const DIVISIONS = ['V', 'IV', 'III', 'II', 'I'];

    private const STARS_PER_DIVISION = 5;

    /** @param  list<array<string, mixed>>  $tiers */
    public function __construct(private readonly array $tiers) {}

    public static function fromConfig(): self
    {
        return new self(config('joki.tiers'));
    }

    private function divisionStars(): int
    {
        return array_sum(array_map(fn (array $tier): int => isset($tier['divisions']) ? $tier['divisions'] * self::STARS_PER_DIVISION : 0, $this->tiers));
    }

    /**
     * Epic V..I, Legend V..I, then one option per Mythic tier, each with its ladder base and star range.
     *
     * @return array<string, array{label: string, base: int, min: int, max: int, mythic: bool}>
     */
    public function rankOptions(): array
    {
        $options = [];
        $base = 0;
        foreach ($this->tiers as $tier) {
            if (empty($tier['divisions'])) {
                $options[$tier['name']] = ['label' => $tier['name'], 'base' => $this->divisionStars(), 'min' => $tier['from'], 'max' => $tier['to'], 'mythic' => true];

                continue;
            }
            foreach (array_slice(self::DIVISIONS, count(self::DIVISIONS) - $tier['divisions']) as $index => $division) {
                $options[$tier['name'].' '.$division] = ['label' => $tier['name'].' '.$division, 'base' => $base + $index * self::STARS_PER_DIVISION, 'min' => 0, 'max' => self::STARS_PER_DIVISION - 1, 'mythic' => false];
            }
            $base += $tier['divisions'] * self::STARS_PER_DIVISION;
        }

        return $options;
    }

    /** The tier whose price applies to the star climbed from this ladder position. */
    private function tierAt(int $position): array
    {
        $start = 0;
        foreach ($this->tiers as $tier) {
            if (! empty($tier['divisions'])) {
                $end = $start + $tier['divisions'] * self::STARS_PER_DIVISION;
                if ($position < $end) {
                    return $tier;
                }
                $start = $end;
            } elseif ($position - $this->divisionStars() <= $tier['to']) {
                return $tier;
            }
        }

        return $this->tiers[array_key_last($this->tiers)];
    }

    /**
     * Price of climbing between two ranks, or null when a rank / star count is invalid or the target is not higher.
     *
     * @return array{stars: int, lines: list<array{name: string, stars: int, price: int}>, subtotal: int, extra: int, total: int, from: string, to: string}|null
     */
    public function quote(string $fromRank, int $fromStars, string $toRank, int $toStars, int $extraPercent = 0): ?array
    {
        $options = $this->rankOptions();
        $from = $options[$fromRank] ?? null;
        $to = $options[$toRank] ?? null;
        if (! $from || ! $to || $fromStars < $from['min'] || $fromStars > $from['max'] || $toStars < $to['min'] || $toStars > $to['max']) {
            return null;
        }
        $start = $from['base'] + $fromStars;
        $end = $to['base'] + $toStars;
        if ($end <= $start) {
            return null;
        }
        $lines = [];
        for ($position = $start; $position < $end; $position++) {
            $tier = $this->tierAt($position);
            $lines[$tier['name']] ??= ['name' => $tier['name'], 'stars' => 0, 'price' => (int) $tier['price']];
            $lines[$tier['name']]['stars']++;
        }
        $subtotal = array_sum(array_map(fn (array $line): int => $line['stars'] * $line['price'], $lines));
        $extra = (int) round($subtotal * $extraPercent / 100);

        return [
            'stars' => $end - $start, 'lines' => array_values($lines), 'subtotal' => $subtotal, 'extra' => $extra, 'total' => $subtotal + $extra,
            'from' => self::describe($from, $fromStars), 'to' => self::describe($to, $toStars),
        ];
    }

    private static function describe(array $option, int $stars): string
    {
        return $option['mythic'] ? $option['label'].' ('.$stars.' bintang Mythic)' : $option['label'].' bintang '.$stars;
    }

    /** Price of a fixed package by its name, from config/joki.php. */
    public static function packagePrice(string $name): ?int
    {
        foreach (config('joki.packages') as $group) {
            foreach ($group as $package) {
                if ($package[0] === $name) {
                    return (int) $package[1];
                }
            }
        }

        return null;
    }
}

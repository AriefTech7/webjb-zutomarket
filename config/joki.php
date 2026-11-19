<?php

/*
 * Joki Mobile Legends price list, shown on /joki-ml and used by its calculator (resources/js/joki.js).
 *
 * Tiers: Epic and Legend have 5 divisions (V..I) of 5 stars each. The Mythic tiers are ranges of Mythic stars:
 * Mythic 0-24, Mythical Honor 25-49, Mythical Glory 50-99, Mythical Immortal 100+. Each star is charged at the
 * price of the tier it is climbed in.
 */
return [
    'tiers' => [
        ['name' => 'Epic', 'price' => 6000, 'divisions' => 5, 'color' => '#6ee7b7'],
        ['name' => 'Legend', 'price' => 7000, 'divisions' => 5, 'color' => '#fdba74'],
        ['name' => 'Mythic', 'price' => 8000, 'from' => 0, 'to' => 24, 'color' => '#c4b5fd'],
        ['name' => 'Mythical Honor', 'price' => 9000, 'from' => 25, 'to' => 49, 'color' => '#f9a8d4'],
        ['name' => 'Mythical Glory', 'price' => 11000, 'from' => 50, 'to' => 99, 'color' => '#fde047'],
        ['name' => 'Mythical Immortal', 'price' => 15000, 'from' => 100, 'to' => 500, 'color' => '#fb7185'],
    ],

    /* Service types. "extra" is a surcharge in percent on the calculated price (0 = same price). */
    'services' => [
        'regular' => ['label' => 'Joki Regular', 'description' => 'Naik rank fleksibel tanpa prioritas role atau hero.', 'extra' => 0],
        'role' => ['label' => 'Prioritas Role', 'description' => 'Utamakan role pilihan selama tersedia.', 'extra' => 0],
        'hero' => ['label' => 'Prioritas Hero', 'description' => 'Utamakan hero pilihan selama tersedia.', 'extra' => 0],
        'gendong' => ['label' => 'Joki Gendong', 'description' => 'Mabar dengan booster lewat akun sendiri, tanpa berbagi password.', 'extra' => 0],
    ],

    'roles' => ['Tank', 'Fighter', 'Assassin', 'Mage', 'Marksman', 'Support'],

    'login_methods' => ['Moonton', 'Facebook', 'Google Play', 'TikTok', 'VK'],

    /* Fixed-price packages from a start rank to a target rank. An optional third value is the struck-out price. */
    'packages' => [
        'Paket Epic' => [['Epic V – Legend V', 150000], ['Epic I – Mythic', 205000], ['Epic II – Mythic', 235000], ['Epic III – Mythic', 265000], ['Epic IV – Mythic', 295000], ['Epic V – Mythic', 325000]],
        'Paket Legend' => [['Legend V – Mythic', 175000], ['Legend IV – Mythic', 140000], ['Legend III – Mythic', 105000], ['Legend II – Mythic', 70000], ['Legend I – Mythic', 35000], ['Legend V – Mythic Honor', 375000], ['Legend V – Mythic Glory', 600000], ['Legend V – Mythic Immortal', 1150000]],
        'Paket Mythic' => [['Mythic – Mythic Honor', 200000], ['Mythic – Mythic Glory', 425000], ['Mythic – Mythic Immortal', 975000]],
        'Paket Mythic Honor & Glory' => [['Honor – Glory', 225000], ['Honor – Immortal', 775000], ['Glory – Immortal', 550000]],
    ],
];

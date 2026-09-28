<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\GameAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            ['Mobile Legends', 'Mythic Glory • Koleksi 150 Skin', 350000, "Rank: Mythic Glory 65\nLevel: 60\nSkin: 150\nHero: 112\nServer: Indonesia", 'arena'],
            ['Valorant', 'Ascendant • Premium Collection', 750000, "Rank: Ascendant\nLevel: 180\nSkin: Reaver Vandal, Prime Phantom\nServer: Asia Pacific", 'strike'],
            ['Genshin Impact', 'AR 60 • Raiden & Furina', 850000, "Level: AR 60\nHero: Raiden Shogun, Furina\nItem: 18 senjata bintang 5\nServer: Asia", 'realm'],
            ['Free Fire', 'Heroic • 12 Evo Gun', 275000, "Rank: Heroic\nLevel: 72\nSkin: 12 Evo Gun\nItem: Elite Pass\nServer: Indonesia", 'strike'],
            ['PUBG Mobile', 'Ace Dominator • M416 Glacier', 600000, "Rank: Ace Dominator\nLevel: 75\nSkin: M416 Glacier\nServer: Asia", 'arena'],
            ['Mobile Legends', 'Legend • Siap Push Rank', 150000, "Rank: Legend\nLevel: 45\nSkin: 65\nHero: 80\nServer: Indonesia", 'realm'],
            ['Valorant', 'Diamond • 8 Premium Skins', 450000, "Rank: Diamond\nLevel: 120\nSkin: 8 premium\nServer: Asia Pacific", 'strike'],
            ['Genshin Impact', 'AR 55 • Starter Exploration', 200000, "Level: AR 55\nHero: Kazuha, Nahida\nItem: 5000 primogem\nServer: Asia", 'realm'],
        ];
        foreach ($games as $index => [$game, $title, $price, $specs, $art]) {
            $category = Category::firstOrCreate(['slug' => Str::slug($game)], ['name' => $game]);
            GameAccount::firstOrCreate(['slug' => Str::slug($game.' '.$title)], [
                'category_id' => $category->id, 'title' => $title, 'price' => $price,
                'description' => 'DATA DEMO — Contoh katalog untuk meninjau tampilan website. Ganti data dan gambar ini dengan akun asli melalui dashboard admin sebelum toko dipublikasikan.',
                'specifications' => $specs, 'main_image' => 'demo/'.$art.'.svg',
                'status' => $index === 6 ? 'sold' : 'available',
            ]);
        }
    }
}

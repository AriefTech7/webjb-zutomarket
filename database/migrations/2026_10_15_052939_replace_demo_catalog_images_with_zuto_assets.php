<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $images = [
            'mobile-legends-mythic-glory-koleksi-150-skin' => 'images/ZUTO BG1.jpg',
            'valorant-ascendant-premium-collection' => 'images/ZUTO BG2.jpg',
            'genshin-impact-ar-60-raiden-furina' => 'images/ZUTO BG3.jpg',
            'free-fire-heroic-12-evo-gun' => 'images/ZUTO BG4.jpg',
            'pubg-mobile-ace-dominator-m416-glacier' => 'images/ZUTO BG5.jpg',
            'mobile-legends-legend-siap-push-rank' => 'images/ZUTO BG1.jpg',
            'valorant-diamond-8-premium-skins' => 'images/ZUTO BG2.jpg',
            'genshin-impact-ar-55-starter-exploration' => 'images/ZUTO BG3.jpg',
        ];

        foreach ($images as $slug => $image) {
            DB::table('game_accounts')
                ->where('slug', $slug)
                ->where('main_image', 'like', 'demo/%')
                ->update(['main_image' => $image]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};

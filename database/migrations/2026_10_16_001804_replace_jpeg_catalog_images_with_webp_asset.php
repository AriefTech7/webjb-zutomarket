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
        DB::table('game_accounts')
            ->whereIn('main_image', [
                'images/ZUTO BG1.jpg',
                'images/ZUTO BG2.jpg',
                'images/ZUTO BG3.jpg',
                'images/ZUTO BG4.jpg',
                'images/ZUTO BG5.jpg',
            ])
            ->update(['main_image' => 'images/zuto-bg1.webp']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};

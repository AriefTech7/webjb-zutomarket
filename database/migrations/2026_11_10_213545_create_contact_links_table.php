<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations, carrying over the contacts the about page used to hard-code.
     */
    public function up(): void
    {
        Schema::create('contact_links', function (Blueprint $table) {
            $table->id();
            $table->string('group', 20)->index();
            $table->string('title', 80);
            $table->string('subtitle', 120)->nullable();
            $table->string('value', 500);
            $table->string('badge', 12)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $setting = fn (string $key): ?string => DB::table('settings')->where('key', $key)->value('value') ?: null;
        $now = now();
        $rows = array_filter([
            $setting('whatsapp_number') ? ['admin', 'Admin '.($setting('store_name') ?? 'SHOPGAME ZUTO'), 'Jual beli akun, joki, cicilan, dan tukar tambah', $setting('whatsapp_number'), 0] : null,
            $setting('whatsapp_rental_number') ? ['admin', 'Admin Rental Akun', 'Khusus sewa akun game', $setting('whatsapp_rental_number'), 1] : null,
            ['social', 'Instagram', '@zutomarket.id', 'https://www.instagram.com/zutomarket.id', 0],
            ['social', 'TikTok', '@aku_zuto', 'https://www.tiktok.com/@aku_zuto', 1],
            ['channel', 'Saluran WhatsApp', 'Ikuti update terbaru', 'https://whatsapp.com/channel/0029VbE83IN3mFXxE4NlTG0L', 0],
        ]);
        DB::table('contact_links')->insert(array_map(fn (array $row): array => [
            'group' => $row[0], 'title' => $row[1], 'subtitle' => $row[2], 'value' => $row[3], 'sort_order' => $row[4], 'created_at' => $now, 'updated_at' => $now,
        ], array_values($rows)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_links');
    }
};

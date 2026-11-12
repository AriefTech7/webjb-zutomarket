<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Home page banners become a list (slider). The single banner that was kept in settings
     * (banner_image / banner_size / banner_link) moves over as the first slide; the image file stays where it is.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->unsignedInteger('width')->default(1280);
            $table->unsignedInteger('height')->default(430);
            $table->string('link_url', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $settings = DB::table('settings')->whereIn('key', ['banner_image', 'banner_size', 'banner_link'])->pluck('value', 'key');
        if (filled($settings['banner_image'] ?? null)) {
            [$width, $height] = array_map(intval(...), explode('x', (string) ($settings['banner_size'] ?? '1280x430')) + [1 => 430]);
            DB::table('banners')->insert([
                'image_path' => $settings['banner_image'],
                'width' => max($width, 1),
                'height' => max($height, 1),
                'link_url' => filled($settings['banner_link'] ?? null) ? $settings['banner_link'] : null,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('settings')->whereIn('key', ['banner_image', 'banner_size', 'banner_link'])->delete();
    }

    public function down(): void
    {
        $first = DB::table('banners')->orderBy('sort_order')->orderBy('id')->first();
        if ($first) {
            foreach (['banner_image' => $first->image_path, 'banner_size' => $first->width.'x'.$first->height, 'banner_link' => (string) $first->link_url] as $key => $value) {
                DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        Schema::dropIfExists('banners');
    }
};

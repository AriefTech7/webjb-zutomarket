<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Text shown under the poster when a visitor opens an "Info Terbaru" item; links in it become clickable.
     */
    public function up(): void
    {
        Schema::table('info_posts', function (Blueprint $table) {
            $table->text('caption')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('info_posts', function (Blueprint $table) {
            $table->dropColumn('caption');
        });
    }
};

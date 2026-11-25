<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-account switch: a super admin account can still be sold through WhatsApp instead of the website checkout.
     * On by default, so every account keeps working as before.
     */
    public function up(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->boolean('use_gateway')->default(true)->after('owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->dropColumn('use_gateway');
        });
    }
};

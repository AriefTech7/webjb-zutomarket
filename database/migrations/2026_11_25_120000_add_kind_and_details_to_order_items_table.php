<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders can now hold a joki (rank boost) service as well as game accounts: "kind" tells them apart and
     * "details" keeps the joki brief (ranks, service, game ID, login method, heroes, notes). Never a password.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('kind', 20)->default('account')->after('game_account_id');
            $table->json('details')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['kind', 'details']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->boolean('is_rental')->default(false)->index();
            $table->timestamp('ready_estimate_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_rental', 'ready_estimate_at']);
        });
    }
};

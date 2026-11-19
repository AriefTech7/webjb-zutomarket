<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Instalment offer shown to visitors for this account. Null = follow the store rules (Admin > Aturan DP & cicilan).
     */
    public function up(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->unsignedTinyInteger('installment_dp_percent')->nullable()->after('original_price');
            $table->unsignedSmallInteger('installment_days')->nullable()->after('installment_dp_percent');
        });
    }

    public function down(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->dropColumn(['installment_dp_percent', 'installment_days']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounts marked "Sudah DP" / "Sudah dicicil": the share of the price already paid and the date the rest is due.
     */
    public function up(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->unsignedTinyInteger('dp_percent')->nullable()->after('status');
            $table->date('payment_due_on')->nullable()->after('dp_percent');
        });
    }

    public function down(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->dropColumn(['dp_percent', 'payment_due_on']);
        });
    }
};

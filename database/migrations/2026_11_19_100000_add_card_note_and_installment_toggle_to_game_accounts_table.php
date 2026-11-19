<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product card badges next to the category: whether the account can be paid in instalments, and a short free note.
     */
    public function up(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->boolean('installment_enabled')->default(true)->after('installment_days');
            $table->string('card_note', 30)->nullable()->after('installment_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->dropColumn(['installment_enabled', 'card_note']);
        });
    }
};

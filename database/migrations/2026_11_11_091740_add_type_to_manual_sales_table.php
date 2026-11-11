<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Sales recorded before this column existed have no known type, so they become "lainnya".
     */
    public function up(): void
    {
        Schema::table('manual_sales', function (Blueprint $table) {
            $table->string('type', 20)->default('lainnya')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_sales', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

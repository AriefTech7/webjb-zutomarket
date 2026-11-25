<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voucher codes for the website checkout (Admin > Voucher). An order keeps the code and the discount it got,
     * so deleting a voucher later does not change past orders.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('type', 10); // percent | fixed
            $table->unsignedInteger('value');
            $table->unsignedInteger('max_discount')->nullable();
            $table->unsignedInteger('min_purchase')->default(0);
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('per_user')->nullable()->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('note', 120)->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->after('subtotal')->constrained()->nullOnDelete();
            $table->string('voucher_code', 30)->nullable()->after('voucher_id');
            $table->unsignedBigInteger('discount')->default(0)->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['voucher_code', 'discount']);
        });
        Schema::dropIfExists('vouchers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Website checkout paid through Tripay. One order holds one or more game accounts; while it is unpaid (and not
     * expired) those accounts cannot be checked out by anyone else.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_ref', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('unpaid')->index();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('payment_method', 30);
            $table->string('payment_name', 80);
            $table->string('tripay_reference', 60)->nullable()->unique();
            $table->string('pay_code', 120)->nullable();
            $table->text('pay_url')->nullable();
            $table->text('qr_url')->nullable();
            $table->json('instructions')->nullable();
            $table->string('customer_name', 60);
            $table->string('customer_email', 120);
            $table->string('customer_phone', 20);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->string('admin_note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->unsignedBigInteger('price');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

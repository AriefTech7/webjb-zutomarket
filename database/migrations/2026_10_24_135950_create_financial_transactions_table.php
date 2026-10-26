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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->uuid('submission_id')->nullable()->unique();
            $table->date('occurred_on')->index();
            $table->foreignId('game_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_account_id')->nullable()->constrained('game_accounts')->nullOnDelete();
            $table->foreignId('trade_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('active_sale_account_id')->nullable()->unique()->constrained('game_accounts')->nullOnDelete();
            $table->foreignId('active_trade_request_id')->nullable()->unique()->constrained('trade_requests')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->unique()->constrained('game_accounts')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('financial_transactions')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('received_title')->nullable();
            $table->string('category_name')->nullable();
            $table->bigInteger('revenue')->default(0);
            $table->bigInteger('cost_of_goods')->default(0);
            $table->bigInteger('operating_expense')->default(0);
            $table->unsignedBigInteger('cash_in')->default(0);
            $table->unsignedBigInteger('cash_out')->default(0);
            $table->unsignedBigInteger('trade_value')->default(0);
            $table->integer('sold_units')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};

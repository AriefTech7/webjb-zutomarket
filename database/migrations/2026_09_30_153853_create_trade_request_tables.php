<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('whatsapp_number', 20);
            $table->string('email');
            $table->text('description');
            $table->unsignedBigInteger('estimated_price')->nullable();
            $table->string('desired_account', 180)->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('trade_request_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_request_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_request_images');
        Schema::dropIfExists('trade_requests');
    }
};

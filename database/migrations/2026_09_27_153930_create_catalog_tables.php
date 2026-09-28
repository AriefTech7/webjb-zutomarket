<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('game_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price');
            $table->text('description');
            $table->text('specifications');
            $table->string('main_image');
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
        Schema::create('game_account_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_account_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_account_images');
        Schema::dropIfExists('game_accounts');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('settings');
    }
};

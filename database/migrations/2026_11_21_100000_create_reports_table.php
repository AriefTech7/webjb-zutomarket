<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public reports of problem users (WhatsApp number) or game IDs. Visitors submit them; they only appear on
     * /laporan after a super admin approves them. The reporter's own contact details are never public.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20);
            $table->string('title', 120);
            $table->string('game_id', 50)->nullable()->index();
            $table->string('wa_number', 20)->nullable()->index();
            $table->text('description');
            $table->json('evidence');
            $table->string('reporter_name', 60);
            $table->string('reporter_email', 120);
            $table->string('reporter_whatsapp', 20);
            $table->string('status', 10)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

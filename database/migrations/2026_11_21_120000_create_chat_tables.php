<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Live chat: one conversation per signed-in buyer, answered by the super admin in Admin > Live chat.
     * *_read_id is the last message id each side has seen, so newer messages are unread for them.
     */
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedBigInteger('buyer_read_id')->default(0);
            $table->unsignedBigInteger('admin_read_id')->default(0);
            $table->timestamps();
        });
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->boolean('from_admin')->default(false);
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};

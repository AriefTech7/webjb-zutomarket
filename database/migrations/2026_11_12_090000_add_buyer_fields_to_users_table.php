<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buyers now share the users table with admins, so admin access becomes an explicit flag.
     * Every user that exists before this migration was created as an admin and keeps that access.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('username');
            $table->string('whatsapp', 20)->nullable()->after('email');
            $table->string('google_id')->nullable()->unique()->after('password');
            $table->json('wishlist')->nullable()->after('google_id');
        });
        DB::table('users')->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('is_admin', false)->delete();
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->change();
            $table->dropUnique(['google_id']);
            $table->dropColumn(['is_admin', 'whatsapp', 'google_id', 'wishlist']);
        });
    }
};

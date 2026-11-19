<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admins only manage their own game accounts; the super admin (the store owner) manages everything.
     * The owner is "razan"; on another install the earliest admin. Existing accounts start out as theirs.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('is_admin');
        });
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('category_id')->constrained('users')->nullOnDelete();
        });

        $admins = DB::table('users')->where('is_admin', true);
        $ownerId = (clone $admins)->where('username', 'razan')->value('id') ?? (clone $admins)->orderBy('id')->value('id');
        if ($ownerId) {
            DB::table('users')->where('id', $ownerId)->update(['is_super_admin' => true]);
            DB::table('game_accounts')->whereNull('owner_id')->update(['owner_id' => $ownerId]);
        }
    }

    public function down(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};

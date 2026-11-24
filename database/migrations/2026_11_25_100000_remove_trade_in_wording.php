<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The store stopped offering trade-ins (payment gateway terms). Remove "tukar tambah" from the stored texts that
     * still advertise it: the admin contact's subtitle and a custom running text, if any.
     */
    public function up(): void
    {
        DB::table('contact_links')->whereIn('subtitle', ['Jual beli akun, joki, dan tukar tambah', 'Jual beli akun, joki, cicilan, dan tukar tambah'])
            ->update(['subtitle' => 'Jual beli akun dan joki', 'updated_at' => now()]);

        $running = DB::table('settings')->where('key', 'running_text')->value('value');
        if (is_string($running) && preg_match('/tukar tambah/i', $running)) {
            $lines = array_filter(preg_split('/\R/', $running), fn (string $line): bool => ! preg_match('/tukar tambah/i', $line));
            DB::table('settings')->where('key', 'running_text')->update(['value' => implode("\n", $lines), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Wording only; nothing to restore.
    }
};

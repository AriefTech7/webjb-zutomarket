<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The store stopped selling on instalments (payment gateway terms). Remove "cicilan" from the stored texts that
     * still advertise it: the admin contact's subtitle (seeded that way) and a custom running text, if any.
     */
    public function up(): void
    {
        DB::table('contact_links')->where('subtitle', 'Jual beli akun, joki, cicilan, dan tukar tambah')
            ->update(['subtitle' => 'Jual beli akun, joki, dan tukar tambah', 'updated_at' => now()]);

        $running = DB::table('settings')->where('key', 'running_text')->value('value');
        if (is_string($running) && str_contains($running, 'Bisa cicil & tukar tambah, tanya admin')) {
            DB::table('settings')->where('key', 'running_text')
                ->update(['value' => str_replace('Bisa cicil & tukar tambah, tanya admin', 'Bisa tukar tambah, tanya admin', $running), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Wording only; nothing to restore.
    }
};

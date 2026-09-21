<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the Angolan Kwanza to installs whose currency list was seeded before
 * AOA joined CurrenciesTableSeeder. A fresh install has an empty table at this
 * point and gets AOA from the seeder instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('currencies')->exists()) {
            return;
        }

        if (DB::table('currencies')->where('code', 'AOA')->exists()) {
            return;
        }

        DB::table('currencies')->insert([
            'name' => 'Angolan Kwanza',
            'code' => 'AOA',
            'symbol' => 'Kz',
            'precision' => 2,
            'thousand_separator' => '.',
            'decimal_separator' => ',',
            'swap_currency_symbol' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Left in place: documents may already be issued in Kwanza.
    }
};

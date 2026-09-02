<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL-only syntax (no doctrine/dbal installed for Blueprint::change());
        // SQLite (used in tests) has dynamic typing, so there's nothing to relax there.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE shein_cart_items MODIFY name VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE shein_cart_items SET name = '' WHERE name IS NULL");
            DB::statement('ALTER TABLE shein_cart_items MODIFY name VARCHAR(255) NOT NULL');
        }
    }
};

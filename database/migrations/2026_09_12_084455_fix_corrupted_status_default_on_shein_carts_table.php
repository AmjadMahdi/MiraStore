<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The earlier split_in_transit_status migration renamed a freshly
     * created string column (status_new, default 'open') to `status`.
     * On MySQL/MariaDB, Laravel's renameColumn() reconstructs the column
     * from information_schema, which already returns string defaults
     * pre-quoted — so the default got re-quoted on top of that, leaving
     * the column's actual DEFAULT as the literal text "'open'" (with the
     * quote characters). Any row inserted without an explicit status
     * since then got that literal string instead of "open".
     */
    public function up(): void
    {
        DB::table('shein_carts')->where('status', "'open'")->update(['status' => 'open']);

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE shein_carts ALTER COLUMN status SET DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        // No meaningful rollback — this only repairs corrupted data/defaults.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "in_transit" is split into two legs (Saudi Arabia, then Yemen) so
     * customers can see more precisely where their order is. The status
     * column also moves off a DB-level ENUM to a plain string here —
     * SheinCart::STATUSES is already the single source of truth for valid
     * values at the application layer, and a plain column means adding or
     * renaming a status in the future never needs driver-specific DDL again.
     */
    public function up(): void
    {
        Schema::table('shein_carts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->string('status_new')->default('open')->after('status');
        });

        DB::statement("UPDATE shein_carts SET status_new = CASE WHEN status = 'in_transit' THEN 'in_transit_sa' ELSE status END");

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('shein_carts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->string('status_old')->default('open')->after('status');
        });

        DB::statement("UPDATE shein_carts SET status_old = CASE WHEN status IN ('in_transit_sa', 'in_transit_ye') THEN 'in_transit' ELSE status END");

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->index('status');
        });
    }
};

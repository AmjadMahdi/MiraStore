<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shein_carts', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('status');
        });

        // Admin-created carts manage their contents via shein_cart_items instead
        // of the raw cart_details blob, so it needs to become optional.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE shein_carts MODIFY cart_details TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shein_carts', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE shein_carts SET cart_details = '' WHERE cart_details IS NULL");
            DB::statement('ALTER TABLE shein_carts MODIFY cart_details TEXT NOT NULL');
        }
    }
};

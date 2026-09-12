<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shein_carts', function (Blueprint $table) {
            $table->string('cart_name')->default('طلبي من Shein')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shein_carts', function (Blueprint $table) {
            $table->string('cart_name')->default('طلبي من شي إن')->change();
        });
    }
};

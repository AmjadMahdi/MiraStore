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
        Schema::create('shein_carts', function (Blueprint $table) {
            $table->id();
            $table->string('cart_number')->unique();
            $table->string('cart_name')->default('My SHEIN Order');
            $table->string('customer_phone')->index();
            $table->text('cart_details');
            $table->enum('status', ['open', 'ordered', 'in_transit', 'arrived'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shein_carts');
    }
};

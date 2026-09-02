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
        Schema::create('shein_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shein_cart_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('link')->nullable();
            $table->date('item_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shein_cart_items');
    }
};

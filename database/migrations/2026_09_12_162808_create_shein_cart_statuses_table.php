<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Makes the cart pipeline admin-manageable: statuses used to be a fixed
     * PHP constant, now they're rows the admin can add, rename, reorder, or
     * remove. Seeded here with the statuses that already exist today so no
     * existing cart's status becomes unrecognized.
     */
    public function up(): void
    {
        Schema::create('shein_cart_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('color')->default('muted');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('shein_cart_statuses')->insert([
            ['key' => 'open', 'label' => 'مفتوحة', 'color' => 'muted', 'display_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'ordered', 'label' => 'تم استلام الطلبات', 'color' => 'info', 'display_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'in_transit_sa', 'label' => 'الطلب في الطريق إلى السعودية', 'color' => 'success', 'display_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'in_transit_ye', 'label' => 'في الطريق إلى اليمن', 'color' => 'warning', 'display_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'arrived', 'label' => 'وصلت إلى تعز', 'color' => 'success', 'display_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'delivered', 'label' => 'تم التسليم', 'color' => 'dark', 'display_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shein_cart_statuses');
    }
};

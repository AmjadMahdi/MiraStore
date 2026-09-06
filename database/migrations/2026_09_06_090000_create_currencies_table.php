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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('symbol');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        $yemeniRiyalId = DB::table('currencies')->insertGetId([
            'code' => 'YER',
            'name' => 'الريال اليمني',
            'symbol' => 'ر.ي',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('currencies')->insert([
            [
                'code' => 'SAR',
                'name' => 'الريال السعودي',
                'symbol' => 'ر.س',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USD',
                'name' => 'الدولار الأمريكي',
                'symbol' => '$',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Every existing product needs a currency — default them all to the
        // Yemeni Riyal (the DB-level default also backfills existing rows).
        Schema::table('products', function (Blueprint $table) use ($yemeniRiyalId) {
            $table->foreignId('currency_id')->default($yemeniRiyalId)->after('vendor_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });

        Schema::dropIfExists('currencies');
    }
};

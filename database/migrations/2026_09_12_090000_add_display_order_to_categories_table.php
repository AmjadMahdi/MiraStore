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
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->default(0)->after('name');
        });

        // Backfill existing categories with their current alphabetical order
        // so the list doesn't visually jump around the first time an admin
        // opens the new reorder UI.
        DB::table('categories')->orderBy('name')->pluck('id')->each(function ($id, $index) {
            DB::table('categories')->where('id', $id)->update(['display_order' => $index]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
    }
};

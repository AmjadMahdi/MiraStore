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
        Schema::table('products', function (Blueprint $table) {
            $table->index('status');
            $table->index(['status', 'is_pinned', 'display_order']);
        });

        // Widen price columns without requiring doctrine/dbal (not installed) for Blueprint::change().
        // MySQL-only syntax; SQLite (used in tests) has dynamic typing, so there's nothing to widen there.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products MODIFY price DECIMAL(10, 2) NOT NULL');
            DB::statement('ALTER TABLE products MODIFY compare_at_price DECIMAL(10, 2) NULL');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active']);
        });

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('interaction_logs', function (Blueprint $table) {
            $table->index(['product_id', 'action_type']);
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'sort_order']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'is_pinned', 'display_order']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products MODIFY price DECIMAL(8, 2) NOT NULL');
            DB::statement('ALTER TABLE products MODIFY compare_at_price DECIMAL(8, 2) NULL');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_active']);
        });

        Schema::table('shein_carts', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('interaction_logs', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'action_type']);
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'sort_order']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};

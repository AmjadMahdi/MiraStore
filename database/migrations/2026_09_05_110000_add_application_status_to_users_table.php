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
        Schema::table('users', function (Blueprint $table) {
            // Defaults to 'approved' so every existing account (staff and
            // already-active vendors) is unaffected by this migration — only
            // brand new vendor registrations are explicitly set to 'pending'.
            $table->enum('application_status', ['pending', 'approved', 'rejected'])->default('approved')->after('role');
            $table->text('application_rejection_reason')->nullable()->after('application_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['application_status', 'application_rejection_reason']);
        });
    }
};

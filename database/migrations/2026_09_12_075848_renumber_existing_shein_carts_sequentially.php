<?php

use App\Models\SheinCart;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-time cleanup: carts created before the mira-N sequential scheme
     * have random MIRA-XXXXX numbers. Renumber them oldest-first so every
     * cart in the system follows the new format.
     */
    public function up(): void
    {
        SheinCart::orderBy('id')
            ->get()
            ->each(function (SheinCart $cart, int $index): void {
                $cart->update(['cart_number' => 'mira-'.(SheinCart::CART_NUMBER_START + $index)]);
            });
    }

    public function down(): void
    {
        // Old random numbers aren't recoverable — nothing to revert to.
    }
};

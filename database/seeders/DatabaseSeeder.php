<?php

namespace Database\Seeders;

use App\Models\InteractionLog;
use App\Models\Product;
use App\Models\SheinCart;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@mirastore.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
                'max_products_limit' => null,
            ]
        );

        $premiumVendors = User::factory()
            ->count(2)
            ->vendor()
            ->premium()
            ->create(['password' => bcrypt('password')]);

        $basicVendors = User::factory()
            ->count(2)
            ->vendor()
            ->create(['password' => bcrypt('password')]);

        $suspendedVendor = User::factory()
            ->vendor()
            ->suspended()
            ->create(['password' => bcrypt('password')]);

        $vendors = $premiumVendors->merge($basicVendors)->push($suspendedVendor);

        foreach ($vendors as $vendor) {
            Product::factory()->count(4)->for($vendor, 'vendor')->create();
            Product::factory()->count(2)->for($vendor, 'vendor')->pending()->create();
            Product::factory()->for($vendor, 'vendor')->rejected()->create();
        }

        foreach ($premiumVendors as $vendor) {
            $vendor->products()->where('status', 'approved')->get()->each(function (Product $product) {
                $views = random_int(20, 200);

                InteractionLog::factory()
                    ->count($views)
                    ->for($product->vendor, 'vendor')
                    ->for($product)
                    ->create(['action_type' => 'view']);

                InteractionLog::factory()
                    ->count((int) round($views * fake()->randomFloat(2, 0.05, 0.3)))
                    ->for($product->vendor, 'vendor')
                    ->for($product)
                    ->create(['action_type' => 'whatsapp_click']);
            });
        }

        SheinCart::factory()->count(3)->create(['status' => 'open']);
        SheinCart::factory()->count(2)->create(['status' => 'ordered']);
        SheinCart::factory()->count(2)->create(['status' => 'in_transit']);
        SheinCart::factory()->count(3)->create(['status' => 'arrived']);
    }
}

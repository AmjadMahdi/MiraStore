<?php

namespace Tests\Feature;

use App\Models\InteractionLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_vendor_sees_an_upgrade_prompt_instead_of_data(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'max_products_limit' => 5]);

        Livewire::actingAs($vendor)
            ->test('vendor.analytics')
            ->assertSee('بريميوم');
    }

    public function test_premium_vendor_sees_view_and_click_counts_per_product(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'max_products_limit' => null]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cute Tote',
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/tote.jpg',
            'status' => 'approved',
        ]);

        InteractionLog::create([
            'vendor_id' => $vendor->id, 'product_id' => $product->id,
            'action_type' => 'view', 'ip_address' => '127.0.0.1',
        ]);
        InteractionLog::create([
            'vendor_id' => $vendor->id, 'product_id' => $product->id,
            'action_type' => 'view', 'ip_address' => '127.0.0.2',
        ]);
        InteractionLog::create([
            'vendor_id' => $vendor->id, 'product_id' => $product->id,
            'action_type' => 'whatsapp_click', 'ip_address' => '127.0.0.1',
        ]);

        Livewire::actingAs($vendor)
            ->test('vendor.analytics')
            ->assertSee('Cute Tote')
            ->assertSee('2') // views
            ->assertSee('50%'); // click-through rate
    }

    public function test_analytics_page_requires_vendor_role(): void
    {
        $this->get(route('vendor.analytics'))->assertRedirect(route('login'));
    }
}

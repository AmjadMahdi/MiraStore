<?php

namespace Tests\Feature;

use App\Models\InteractionLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function approvedProduct(array $overrides = []): Product
    {
        $vendor = User::factory()->create(array_merge([
            'role' => 'vendor',
            'store_name' => "Amina's Bakes",
            'whatsapp_number' => '+9677700000',
        ], $overrides));

        return Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cute Tote',
            'description' => 'A very nice bag',
            'price' => 19.99,
            'image_path' => 'products/tote.jpg',
            'status' => 'approved',
        ]);
    }

    public function test_store_page_lists_only_approved_products(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $approved = Product::create([
            'vendor_id' => $vendor->id, 'name' => 'Visible', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/a.jpg', 'status' => 'approved',
        ]);

        Product::create([
            'vendor_id' => $vendor->id, 'name' => 'Hidden', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/b.jpg', 'status' => 'pending',
        ]);

        Livewire::test('store.product-grid', ['vendor' => $vendor])
            ->assertSee('Visible')
            ->assertDontSee('Hidden');
    }

    public function test_viewing_a_product_page_logs_an_interaction(): void
    {
        $product = $this->approvedProduct();

        $this->get(route('store.product', [$product->vendor, $product]))
            ->assertOk()
            ->assertSee('Order via WhatsApp');

        $this->assertDatabaseHas('interaction_logs', [
            'vendor_id' => $product->vendor_id,
            'product_id' => $product->id,
            'action_type' => 'view',
        ]);
    }

    public function test_pending_product_page_is_not_publicly_visible(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $product = Product::create([
            'vendor_id' => $vendor->id, 'name' => 'Pending', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/a.jpg', 'status' => 'pending',
        ]);

        $this->get(route('store.product', [$vendor, $product]))->assertNotFound();
    }

    public function test_whatsapp_click_logs_interaction_and_redirects_to_wa_me(): void
    {
        $product = $this->approvedProduct();

        $response = $this->get(route('store.product.contact', [$product->vendor, $product]));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/9677700000', $response->headers->get('Location'));

        $this->assertDatabaseHas('interaction_logs', [
            'vendor_id' => $product->vendor_id,
            'product_id' => $product->id,
            'action_type' => 'whatsapp_click',
        ]);
    }

    public function test_contact_fails_for_vendor_with_no_whatsapp_number(): void
    {
        $product = $this->approvedProduct(['whatsapp_number' => null]);

        $this->get(route('store.product.contact', [$product->vendor, $product]))->assertNotFound();
    }
}

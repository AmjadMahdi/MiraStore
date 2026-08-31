<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VendorProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_vendor_can_create_a_product_which_starts_pending(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Cute Tote')
            ->set('description', 'A very nice bag')
            ->set('price', '19.99')
            ->set('stock_status', 'in_stock')
            ->set('image', UploadedFile::fake()->image('tote.jpg', 1200, 900))
            ->call('save')
            ->assertRedirect(route('vendor.products.index'));

        $product = Product::first();

        $this->assertSame('Cute Tote', $product->name);
        $this->assertSame('pending', $product->status);
        $this->assertSame($vendor->id, $product->vendor_id);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_vendor_cannot_exceed_product_limit(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'max_products_limit' => 1]);

        Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Existing',
            'description' => 'desc',
            'price' => 5,
            'image_path' => 'products/existing.jpg',
        ]);

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Second item')
            ->set('description', 'desc')
            ->set('price', '5')
            ->set('image', UploadedFile::fake()->image('two.jpg'))
            ->call('save')
            ->assertHasErrors('name');

        $this->assertSame(1, Product::count());
    }

    public function test_vendor_cannot_edit_another_vendors_product(): void
    {
        $owner = User::factory()->create(['role' => 'vendor']);
        $intruder = User::factory()->create(['role' => 'vendor']);

        $product = Product::create([
            'vendor_id' => $owner->id,
            'name' => 'Not yours',
            'description' => 'desc',
            'price' => 5,
            'image_path' => 'products/x.jpg',
        ]);

        $this->actingAs($intruder);

        Livewire::test('vendor.product-form', ['product' => $product])
            ->assertForbidden();
    }

    public function test_vendor_can_delete_own_product(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Doomed',
            'description' => 'desc',
            'price' => 5,
            'image_path' => 'products/doomed.jpg',
        ]);

        Livewire::actingAs($vendor)
            ->test('vendor.product-list')
            ->call('delete', $product->id);

        $this->assertSoftDeleted($product);
    }
}

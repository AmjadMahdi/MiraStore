<?php

namespace Tests\Feature;

use App\Models\Category;
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
        $category = Category::factory()->create();

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Cute Tote')
            ->set('category_id', (string) $category->id)
            ->set('description', 'A very nice bag')
            ->set('price', '19.99')
            ->set('stock_status', 'in_stock')
            ->set('newImages', [UploadedFile::fake()->image('tote.jpg', 1200, 900)])
            ->call('save')
            ->assertRedirect(route('vendor.products.index'));

        $product = Product::first();

        $this->assertSame('Cute Tote', $product->name);
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame('pending', $product->status);
        $this->assertSame($vendor->id, $product->vendor_id);
        $this->assertSame(1, $product->images()->count());
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_vendor_can_upload_multiple_images_for_a_product(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Cute Tote')
            ->set('category_id', (string) $category->id)
            ->set('description', 'A very nice bag')
            ->set('price', '19.99')
            ->set('newImages', [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ])
            ->call('save')
            ->assertRedirect(route('vendor.products.index'));

        $product = Product::first();

        $this->assertSame(3, $product->images()->count());
        $this->assertSame($product->images()->orderBy('sort_order')->first()->path, $product->image_path);
    }

    public function test_product_creation_requires_a_category(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Cute Tote')
            ->set('description', 'A very nice bag')
            ->set('price', '19.99')
            ->set('newImages', [UploadedFile::fake()->image('tote.jpg')])
            ->call('save')
            ->assertHasErrors('category_id');

        $this->assertSame(0, Product::count());
    }

    public function test_product_creation_requires_at_least_one_image(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Cute Tote')
            ->set('category_id', (string) $category->id)
            ->set('description', 'A very nice bag')
            ->set('price', '19.99')
            ->call('save')
            ->assertHasErrors('newImages');

        $this->assertSame(0, Product::count());
    }

    public function test_vendor_cannot_exceed_product_limit(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'max_products_limit' => 1]);
        $category = Category::factory()->create();

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Existing',
            'description' => 'desc',
            'price' => 5,
            'image_path' => 'products/existing.jpg',
        ]);

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Second item')
            ->set('category_id', (string) $category->id)
            ->set('description', 'desc')
            ->set('price', '5')
            ->set('newImages', [UploadedFile::fake()->image('two.jpg')])
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

    public function test_vendor_can_remove_an_image_but_not_the_last_one(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Cute Tote',
            'description' => 'desc',
            'price' => 5,
            'image_path' => 'products/a.jpg',
            'status' => 'approved',
            'stock_status' => 'in_stock',
        ]);

        $imageA = $product->images()->create(['path' => 'products/a.jpg', 'sort_order' => 0]);
        $imageB = $product->images()->create(['path' => 'products/b.jpg', 'sort_order' => 1]);

        Livewire::actingAs($vendor)
            ->test('vendor.product-form', ['product' => $product])
            ->call('removeExistingImage', $imageB->id);

        $this->assertSame(1, $product->images()->count());
        $this->assertSame('pending', $product->fresh()->status);

        Livewire::actingAs($vendor)
            ->test('vendor.product-form', ['product' => $product])
            ->call('removeExistingImage', $imageA->id)
            ->assertHasErrors('newImages');

        $this->assertSame(1, $product->images()->count());
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

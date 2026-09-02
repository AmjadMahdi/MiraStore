<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function approvedProduct(string $name): Product
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        return Product::create([
            'vendor_id' => $vendor->id,
            'name' => $name,
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/tote.jpg',
            'status' => 'approved',
        ]);
    }

    public function test_admin_sees_approved_products_ordered_by_display_order(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $first = $this->approvedProduct('First');
        $second = $this->approvedProduct('Second');
        $second->update(['display_order' => 0]);
        $first->update(['display_order' => 1]);

        Livewire::actingAs($admin)
            ->test('admin.product-order')
            ->assertSet('orderedIds', [$second->id, $first->id]);
    }

    public function test_admin_can_drag_a_product_to_reorder_it(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $one = $this->approvedProduct('One');
        $two = $this->approvedProduct('Two');
        $three = $this->approvedProduct('Three');

        Livewire::actingAs($admin)
            ->test('admin.product-order')
            ->assertSet('orderedIds', [$one->id, $two->id, $three->id])
            ->call('moveItem', 2, 0)
            ->assertSet('orderedIds', [$three->id, $one->id, $two->id]);

        $this->assertSame(0, $three->fresh()->display_order);
        $this->assertSame(1, $one->fresh()->display_order);
        $this->assertSame(2, $two->fresh()->display_order);
    }

    public function test_pending_products_are_not_listed_for_ordering(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        $pending = Product::create([
            'vendor_id' => $vendor->id, 'name' => 'Pending', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/p.jpg', 'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.product-order')
            ->assertDontSee('Pending');
    }

    public function test_homepage_grid_respects_display_order(): void
    {
        $first = $this->approvedProduct('Should Be First');
        $second = $this->approvedProduct('Should Be Second');
        $first->update(['display_order' => 0]);
        $second->update(['display_order' => 1]);

        $html = Livewire::test('product-grid')->html();

        $this->assertLessThan(
            strpos($html, 'Should Be Second'),
            strpos($html, 'Should Be First')
        );
    }

    public function test_homepage_grid_ranks_platform_store_then_partners_then_others(): void
    {
        $regularVendor = User::factory()->create(['role' => 'vendor']);
        $regular = Product::create([
            'vendor_id' => $regularVendor->id, 'name' => 'Regular Product', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/r.jpg', 'status' => 'approved',
        ]);

        $partnerVendor = User::factory()->create(['role' => 'vendor', 'is_verified' => true]);
        $partner = Product::create([
            'vendor_id' => $partnerVendor->id, 'name' => 'Partner Product', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/pt.jpg', 'status' => 'approved',
        ]);

        $platformVendor = User::factory()->create(['role' => 'vendor', 'is_platform_store' => true]);
        $platform = Product::create([
            'vendor_id' => $platformVendor->id, 'name' => 'Platform Product', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/pl.jpg', 'status' => 'approved',
        ]);

        // Created in an order that would normally put "Regular" first via latest(); the tiering should override that.
        $html = Livewire::test('product-grid')->html();

        $platformPos = strpos($html, 'Platform Product');
        $partnerPos = strpos($html, 'Partner Product');
        $regularPos = strpos($html, 'Regular Product');

        $this->assertLessThan($partnerPos, $platformPos);
        $this->assertLessThan($regularPos, $partnerPos);
    }

    public function test_admin_can_pin_a_product_to_the_top(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $one = $this->approvedProduct('One');
        $two = $this->approvedProduct('Two');
        $three = $this->approvedProduct('Three');

        Livewire::actingAs($admin)
            ->test('admin.product-order')
            ->assertSet('orderedIds', [$one->id, $two->id, $three->id])
            ->call('togglePin', $three->id)
            ->assertSet('orderedIds', [$three->id, $one->id, $two->id]);

        $this->assertTrue($three->fresh()->is_pinned);
    }

    public function test_admin_can_unpin_a_product(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $pinned = $this->approvedProduct('Pinned');
        $pinned->update(['is_pinned' => true]);

        Livewire::actingAs($admin)
            ->test('admin.product-order')
            ->call('togglePin', $pinned->id);

        $this->assertFalse($pinned->fresh()->is_pinned);
    }

    public function test_pinning_does_not_revert_an_approved_products_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->approvedProduct('Stays Approved');

        Livewire::actingAs($admin)
            ->test('admin.product-order')
            ->call('togglePin', $product->id);

        $this->assertSame('approved', $product->fresh()->status);
    }

    public function test_homepage_grid_shows_pinned_products_first_regardless_of_vendor_tier(): void
    {
        $platformVendor = User::factory()->create(['role' => 'vendor', 'is_platform_store' => true]);
        Product::create([
            'vendor_id' => $platformVendor->id, 'name' => 'Platform Product', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/pl.jpg', 'status' => 'approved',
        ]);

        $regularVendor = User::factory()->create(['role' => 'vendor']);
        $pinned = Product::create([
            'vendor_id' => $regularVendor->id, 'name' => 'Pinned Regular Product', 'description' => 'd',
            'price' => 10, 'image_path' => 'products/r.jpg', 'status' => 'approved',
        ]);
        $pinned->update(['is_pinned' => true]);

        $html = Livewire::test('product-grid')->html();

        $this->assertLessThan(
            strpos($html, 'Platform Product'),
            strpos($html, 'Pinned Regular Product')
        );
    }

    public function test_vendor_cannot_access_product_ordering(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.products.order'))
            ->assertForbidden();
    }
}
